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
            'message_timestamp' => 'nullable|string', // Timestamp del mensaje del cliente
            'response_timestamp' => 'nullable|string', // Timestamp de la respuesta del bot
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

            // 4. IMPORTANTE: n8n puede enviar message_text Y response en el mismo webhook
            // Si ambos están presentes, debemos crear DOS registros separados:
            // - Registro 1: Mensaje del cliente (is_client_message: true, solo message_text)
            // - Registro 2: Respuesta del bot (is_client_message: false, solo response)
            
            // 4.1. Guardar mensaje del cliente (si existe y no está guardado ya)
            if ($request->filled('message') && $request->filled('message_id')) {
                $existingClientMessage = $lead->conversations()
                    ->where('message_id', $request->message_id)
                    ->first();
                
                if (!$existingClientMessage) {
                    // El mensaje del cliente no existe, crearlo
                    $messageTimestamp = $request->filled('message_timestamp') 
                        ? $request->message_timestamp 
                        : now()->toDateTimeString();
                    
                    $lead->conversations()->create([
                        'user_id' => $user->id,
                        'message_id' => $request->message_id,
                        'message_text' => $request->message, // SOLO message_text, NO response
                        'is_client_message' => true, // BLANCO, IZQUIERDA
                        'is_employee' => false,
                        'platform' => 'whatsapp',
                        'timestamp' => $messageTimestamp,
                        'message_length' => strlen($request->message),
                    ]);
                    
                    Log::info("✅ Mensaje del cliente guardado desde n8n", [
                        'lead_id' => $lead->id,
                        'message_id' => $request->message_id,
                    ]);
                }
            }
            
            // 4.2. Guardar respuesta del bot (si existe)
            if ($request->filled('response') && $request->filled('response_id')) {
                try {
                    // Verificar si la respuesta ya existe (evitar duplicados)
                    $existingResponse = $lead->conversations()
                        ->where('message_id', $request->response_id)
                        ->first();
                    
                    if (!$existingResponse) {
                        // Crear nueva conversación para la respuesta del bot
                        $responseTimestamp = $request->filled('response_timestamp') 
                            ? $request->response_timestamp 
                            : now()->toDateTimeString();
                        
                        $conversation = $lead->conversations()->create([
                            'user_id' => $user->id,
                            'message_id' => $request->response_id,
                            'message_text' => $request->response, // Para mostrar en el chat
                            'response' => $request->response, // También en response
                            'is_client_message' => false, // VERDE, DERECHA
                            'is_employee' => true, // Es del bot/empleado
                            'platform' => 'whatsapp',
                            'timestamp' => $responseTimestamp,
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
