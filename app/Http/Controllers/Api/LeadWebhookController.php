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

            // 4. Create Conversation Record for the CLIENT message (if exists)
            // IMPORTANTE: Solo guardar si no existe ya (evitar duplicados desde WhatsApp webhook)
            if ($request->filled('message') && $request->filled('message_id')) {
                // Verificar si el mensaje ya existe (evitar duplicados)
                $existingMessage = $lead->conversations()
                    ->where('message_id', $request->message_id)
                    ->where('is_client_message', true)
                    ->first();
                
                if (!$existingMessage) {
                    $lead->conversations()->create([
                        'user_id' => $user->id,
                        'message_id' => $request->message_id,
                        'message_text' => $request->message,
                        'response' => null, // Los mensajes del cliente NO tienen response
                        'is_client_message' => true,
                        'is_employee' => false,
                        'platform' => 'whatsapp',
                        'timestamp' => now()->toDateTimeString(),
                        'message_length' => strlen($request->message),
                    ]);
                    
                    Log::info("✅ Mensaje del cliente guardado", [
                        'lead_id' => $lead->id,
                        'message_id' => $request->message_id,
                    ]);
                } else {
                    Log::info("⚠️ Mensaje del cliente ya existe, omitiendo duplicado", [
                        'lead_id' => $lead->id,
                        'message_id' => $request->message_id,
                        'existing_id' => $existingMessage->id,
                    ]);
                }
            }

            // 5. Create Conversation Record for the MODEL/BOT response (if exists)
            // IMPORTANTE: Solo guardar si no existe ya (evitar duplicados)
            if ($request->filled('response') && $request->filled('response_id')) {
                try {
                    // Verificar si la respuesta ya existe (evitar duplicados)
                    $existingResponse = $lead->conversations()
                        ->where('message_id', $request->response_id)
                        ->where('is_client_message', false)
                        ->first();
                    
                    if (!$existingResponse) {
                        $conversation = $lead->conversations()->create([
                            'user_id' => $user->id,
                            'message_id' => $request->response_id,
                            'message_text' => null, // Las respuestas del bot NO tienen message_text del cliente
                            'response' => $request->response, // La respuesta del bot va aquí
                            'is_client_message' => false, // Es respuesta del sistema/bot
                            'is_employee' => false, // NO es empleado, es el bot/IA
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
                        Log::info("⚠️ Respuesta del modelo ya existe, omitiendo duplicado", [
                            'lead_id' => $lead->id,
                            'response_id' => $request->response_id,
                            'existing_id' => $existingResponse->id,
                        ]);
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

            // 6. Trigger AI Analysis (solo si hay mensaje del cliente, no si solo es respuesta)
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
