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
            'lead_level' => 'nullable|string', // Nivel del lead desde n8n
            'stage' => 'nullable|string', // Etapa del lead desde n8n
            'confidence_score' => 'nullable|numeric', // Confianza desde n8n
            'message_id' => 'nullable|string', // ID del mensaje de WhatsApp
            'response_id' => 'nullable|string', // ID de la respuesta enviada
            'message_timestamp' => 'nullable|string', // Timestamp del mensaje del cliente
            'response_timestamp' => 'nullable|string', // Timestamp de la respuesta del bot
        ]);

        try {
            // 3. Create or Update Lead
            // IMPORTANTE: Buscar primero por phone_number (sin importar user_id) para evitar duplicados
            // Si existe un lead con ese phone_number, actualizarlo. Si no, crearlo con el user_id actual.
            $phoneNumber = $request->client_phone;
            
            // Buscar lead existente por phone_number (sin importar user_id)
            $lead = Lead::where('phone_number', $phoneNumber)->first();
            
            if ($lead) {
                // Lead existe, actualizar campos
                $updateData = [
                    'updated_at' => now(),
                ];
                
                // Actualizar user_id si es diferente (para asegurar que pertenezca al usuario correcto)
                if ($lead->user_id != $user->id) {
                    $updateData['user_id'] = $user->id;
                    Log::info("⚠️ Lead encontrado con diferente user_id, actualizando", [
                        'lead_id' => $lead->id,
                        'old_user_id' => $lead->user_id,
                        'new_user_id' => $user->id,
                    ]);
                }
                
                // Actualizar campos solo si n8n los envía
                if ($request->filled('client_name')) {
                    $updateData['client_name'] = $request->client_name;
                }
                if ($request->filled('intent')) {
                    $updateData['intent'] = $request->intent;
                }
                if ($request->filled('lead_level')) {
                    $updateData['lead_level'] = $request->lead_level;
                }
                if ($request->filled('stage')) {
                    $updateData['stage'] = $request->stage;
                }
                if ($request->filled('confidence_score')) {
                    $updateData['confidence_score'] = (float) $request->confidence_score;
                }
                
                $lead->update($updateData);
                
                Log::info("✅ Lead existente actualizado", [
                    'lead_id' => $lead->id,
                    'phone_number' => $phoneNumber,
                    'updated_fields' => array_keys($updateData),
                ]);
            } else {
                // Lead no existe, crearlo
                $leadData = [
                    'user_id' => $user->id,
                    'phone_number' => $phoneNumber,
                    'client_name' => $request->client_name ?? 'Desconocido',
                    'intent' => $request->intent ?? 'consulta',
                    'lead_level' => $request->lead_level ?? 'cold',
                    'stage' => $request->stage ?? 'nuevo',
                    'confidence_score' => $request->filled('confidence_score') ? (float) $request->confidence_score : 0.0,
                    'updated_at' => now(),
                ];
                
                $lead = Lead::create($leadData);
                
                Log::info("✅ Lead nuevo creado", [
                    'lead_id' => $lead->id,
                    'phone_number' => $phoneNumber,
                    'user_id' => $user->id,
                ]);
            }

            // 4. IMPORTANTE: n8n puede enviar message_text Y response en el mismo webhook
            // Si ambos están presentes, debemos crear DOS registros separados:
            // - Registro 1: Mensaje del cliente (is_client_message: true, solo message_text)
            // - Registro 2: Respuesta del bot (is_client_message: false, solo response)
            
            // 4.1. IMPORTANTE: El mensaje del cliente YA fue guardado por WhatsAppWebhookController
            // Buscar el mensaje del cliente más reciente para usarlo como referencia para el timestamp de la respuesta
            $clientMessage = null;
            
            if ($request->filled('message') && $request->filled('message_id')) {
                // Buscar el mensaje del cliente por message_id + lead_id para evitar duplicados
                $clientMessage = $lead->conversations()
                    ->where('message_id', $request->message_id)
                    ->where('lead_id', $lead->id)
                    ->where('is_client_message', true)
                    ->first();
                
                // Si no se encuentra por message_id, verificar por message_text + lead_id
                if (!$clientMessage) {
                    $clientMessage = $lead->conversations()
                        ->where('message_text', $request->message)
                        ->where('lead_id', $lead->id)
                        ->where('is_client_message', true)
                        ->whereNull('response') // Asegurar que no sea una respuesta del bot
                        ->first();
                }
                
                if (!$clientMessage) {
                    // El mensaje del cliente no existe, crearlo
                    $messageTimestamp = $request->filled('message_timestamp') 
                        ? $request->message_timestamp 
                        : now()->subMinutes(1)->toDateTimeString(); // 1 minuto antes para asegurar orden
                    
                    $clientMessage = $lead->conversations()->create([
                        'user_id' => $user->id,
                        'message_id' => $request->message_id,
                        'message_text' => $request->message, // SOLO message_text, NO response
                        'response' => null, // Asegurar que response esté vacío
                        'is_client_message' => true, // BLANCO, IZQUIERDA
                        'is_employee' => false,
                        'platform' => 'whatsapp',
                        'timestamp' => $messageTimestamp,
                        'message_length' => strlen($request->message),
                    ]);
                    
                    Log::info("✅ Mensaje del cliente guardado desde n8n (caso edge)", [
                        'lead_id' => $lead->id,
                        'message_id' => $request->message_id,
                        'message_text' => $request->message,
                    ]);
                }
            }
            
            // Si no encontramos el mensaje del cliente pero hay response_id, buscar el último mensaje del cliente
            // Esto asegura que siempre tengamos una referencia para calcular el timestamp de la respuesta
            if (!$clientMessage && $request->filled('response_id')) {
                $clientMessage = $lead->conversations()
                    ->where('is_client_message', true)
                    ->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($clientMessage) {
                    Log::info("✅ Mensaje del cliente encontrado para referencia de timestamp", [
                        'lead_id' => $lead->id,
                        'client_message_id' => $clientMessage->id,
                        'message_id' => $clientMessage->message_id,
                    ]);
                }
            }
            
            // 4.2. Guardar respuesta del bot (si existe)
            // IMPORTANTE: La respuesta del bot debe tener un timestamp DESPUÉS del mensaje del cliente
            if ($request->filled('response') && $request->filled('response_id')) {
                try {
                    // Verificar si la respuesta ya existe (evitar duplicados)
                    // IMPORTANTE: Verificar por response_id + lead_id para evitar duplicados exactos
                    $existingResponse = $lead->conversations()
                        ->where('message_id', $request->response_id)
                        ->where('lead_id', $lead->id)
                        ->first();
                    
                    // Si no se encuentra por response_id, verificar por response + lead_id + is_employee
                    if (!$existingResponse) {
                        $existingResponse = $lead->conversations()
                            ->where('response', $request->response)
                            ->where('lead_id', $lead->id)
                            ->where('is_client_message', false)
                            ->where('is_employee', true)
                            ->where('message_text', null) // Asegurar que es una respuesta del bot
                            ->first();
                    }
                    
                    if (!$existingResponse) {
                        // Calcular timestamp de la respuesta:
                        // La respuesta del bot debe ir DESPUÉS del mensaje del cliente
                        // Buscar el mensaje del cliente más reciente para usar su timestamp
                        $lastClientMessage = null;
                        
                        if ($clientMessage) {
                            $lastClientMessage = $clientMessage;
                        } else {
                            // Buscar el último mensaje del cliente (por si no se encontró con message_id)
                            $lastClientMessage = $lead->conversations()
                                ->where('is_client_message', true)
                                ->orderBy('created_at', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                        }
                        
                        if ($lastClientMessage) {
                            // Obtener el timestamp del mensaje del cliente
                            $clientTimestamp = $lastClientMessage->created_at 
                                ?? \Carbon\Carbon::parse($lastClientMessage->timestamp ?? now());
                            
                            // La respuesta debe ir DESPUÉS del mensaje del cliente (al menos 2 segundos después)
                            $responseTimestamp = $clientTimestamp->copy()->addSeconds(2)->toDateTimeString();
                            
                            Log::info("✅ Timestamp de respuesta calculado basado en mensaje del cliente", [
                                'lead_id' => $lead->id,
                                'client_message_id' => $lastClientMessage->id,
                                'client_timestamp' => $clientTimestamp->toDateTimeString(),
                                'response_timestamp' => $responseTimestamp,
                            ]);
                        } elseif ($request->filled('response_timestamp')) {
                            $responseTimestamp = $request->response_timestamp;
                            Log::info("✅ Usando response_timestamp de n8n", [
                                'lead_id' => $lead->id,
                                'response_timestamp' => $responseTimestamp,
                            ]);
                        } else {
                            // Si no hay mensaje del cliente, usar timestamp actual menos 1 segundo
                            $responseTimestamp = now()->subSeconds(1)->toDateTimeString();
                            Log::warning("⚠️ No se encontró mensaje del cliente, usando timestamp actual", [
                                'lead_id' => $lead->id,
                                'response_timestamp' => $responseTimestamp,
                            ]);
                        }
                        
                        // IMPORTANTE: Para la respuesta del bot:
                        // - message_text debe estar vacío o null (el mensaje del cliente ya está en otro registro)
                        // - response contiene la respuesta del bot
                        $conversation = $lead->conversations()->create([
                            'user_id' => $user->id,
                            'message_id' => $request->response_id,
                            'message_text' => null, // VACÍO - el mensaje del cliente está en otro registro
                            'response' => $request->response, // SOLO la respuesta del bot
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
                        Log::info("⚠️ Respuesta del bot ya existe, evitando duplicado", [
                            'lead_id' => $lead->id,
                            'conversation_id' => $existingResponse->id,
                            'response_id' => $request->response_id,
                        ]);
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
