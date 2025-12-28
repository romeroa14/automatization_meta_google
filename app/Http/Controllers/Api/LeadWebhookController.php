<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class LeadWebhookController extends Controller
{
    /**
     * Handle incoming webhook from n8n.
     * 
     * IMPORTANTE: Este endpoint requiere autenticación con token de Sanctum.
     * El token se genera desde: https://app.admetricas.com/profile
     */
    public function handle(Request $request)
    {
        // 1. Authenticated User (via Sanctum Token)
        $user = $request->user();

        // Log completo del payload recibido para debugging
        Log::info('📥 Webhook recibido desde n8n', [
            'user_id' => $user->id,
            'payload' => $request->all(),
            'has_message' => $request->filled('message'),
            'has_response' => $request->filled('response'),
        ]);

        // 2. Validate Payload
        $request->validate([
            'client_phone' => 'required|string',
            'client_name' => 'nullable|string',
            'message' => 'nullable|string', // Mensaje del cliente
            'response' => 'nullable|string', // Respuesta del modelo/n8n
            'intent' => 'nullable|string',
            'message_id' => 'nullable|string', // ID del mensaje de WhatsApp
            'response_id' => 'nullable|string', // ID de la respuesta enviada
        ]);

        try {
            // 3. Create or Update Lead
            $lead = Lead::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'phone_number' => $request->client_phone,
                ],
                [
                    'client_name' => $request->client_name ?? 'Desconocido',
                    'intent' => $request->intent ?? 'consulta',
                    'lead_level' => 'cold', // Default
                    'stage' => 'nuevo',
                    'confidence_score' => 0.0,
                    'updated_at' => now(),
                ]
            );

            // 4. IMPORTANTE: NO guardamos el mensaje del cliente aquí
            // El mensaje del cliente YA fue guardado por WhatsAppWebhookController cuando llegó desde WhatsApp
            // Este webhook de n8n solo debe guardar la RESPUESTA del bot que n8n ya envió a WhatsApp
            
            // 5. Create Conversation Record for the MODEL/BOT response (if exists)
            // IMPORTANTE: La respuesta del bot es un registro SEPARADO del mensaje del cliente
            if ($request->filled('response') && $request->filled('response_id')) {
                try {
                    // Verificar si la respuesta ya existe (evitar duplicados)
                    $existingResponse = $lead->conversations()
                        ->where('message_id', $request->response_id)
                        ->first();
                    
                    if (!$existingResponse) {
                        // Crear nueva conversación para la respuesta del bot
                        $conversation = $lead->conversations()->create([
                            'user_id' => $user->id,
                            'message_id' => $request->response_id,
                            'response' => $request->response, // La respuesta va en el campo 'response'
                            'message_text' => $request->response, // También en message_text para mostrar en el chat
                            'is_client_message' => false, // Es respuesta del sistema/bot
                            'is_employee' => true, // Es del bot/empleado
                            'platform' => 'whatsapp',
                            'timestamp' => now()->toDateTimeString(),
                            'message_length' => strlen($request->response),
                            'status' => 'sent',
                        ]);
                        
                        Log::info("✅ Respuesta del modelo guardada exitosamente", [
                            'lead_id' => $lead->id,
                            'conversation_id' => $conversation->id,
                            'response_id' => $request->response_id,
                            'response_length' => strlen($request->response),
                        ]);
                    } else {
                        // La respuesta ya existe, actualizar si es necesario
                        if ($existingResponse->response !== $request->response) {
                            $existingResponse->update([
                                'response' => $request->response,
                                'message_text' => $request->response,
                            ]);
                            Log::info("✅ Respuesta del modelo actualizada", [
                                'lead_id' => $lead->id,
                                'conversation_id' => $existingResponse->id,
                                'response_id' => $request->response_id,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("❌ Error guardando respuesta del modelo", [
                        'lead_id' => $lead->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                Log::warning("⚠️ Webhook recibido sin campo 'response' o 'response_id'", [
                    'lead_id' => $lead->id,
                    'has_response' => $request->filled('response'),
                    'has_response_id' => $request->filled('response_id'),
                    'payload_keys' => array_keys($request->all()),
                ]);
            }

            // 6. Trigger AI Analysis (solo si hay mensaje del cliente nuevo)
            // NOTA: El mensaje del cliente ya fue procesado en WhatsAppWebhookController
            // Este webhook solo guarda la respuesta del bot que n8n ya envió a WhatsApp
            if ($request->filled('message')) {
                \App\Jobs\AnalyzeLeadJob::dispatch($lead->id);
            }

            // 7. Log interaction
            Log::info("Lead procesado desde n8n", [
                'user_id' => $user->id,
                'lead_id' => $lead->id,
                'client_name' => $lead->client_name,
                'has_message' => $request->filled('message'),
                'has_response' => $request->filled('response'),
            ]);

            return response()->json([
                'success' => true,
                'lead_id' => $lead->id,
                'message' => 'Lead procesado correctamente',
            ]);

        } catch (\Exception $e) {
            Log::error("Error processing lead webhook: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error inteno al procesar el lead'
            ], 500);
        }
    }
}
