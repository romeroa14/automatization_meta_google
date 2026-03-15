<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppLeadService;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\WhatsAppPhoneNumber;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::info('WhatsApp webhook verification attempt', [
            'mode' => $mode,
            'token' => $token,
        ]);

        if ($mode === 'subscribe') {
            // Buscar número de WhatsApp por verify_token
            $whatsappNumber = WhatsAppPhoneNumber::where('verify_token', $token)->first();
            
            if ($whatsappNumber) {
                Log::info('WhatsApp webhook verified successfully', [
                    'organization_id' => $whatsappNumber->organization_id,
                    'phone_number' => $whatsappNumber->phone_number,
                ]);
                return response($challenge, 200);
            }
            
            // Fallback al token global para compatibilidad
            $verifyToken = config('services.whatsapp.verify_token');
            if ($token === $verifyToken) {
                Log::info('WhatsApp webhook verified with global token');
                return response($challenge, 200);
            }
        }

        Log::warning('WhatsApp webhook verification failed', ['token' => $token]);
        return response('Forbidden', 403);
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();
        
        Log::info('WhatsApp webhook received', $data);

        // Verificar si hay mensajes
        if (isset($data['entry']) && is_array($data['entry'])) {
            foreach ($data['entry'] as $entry) {
                if (isset($entry['changes']) && is_array($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if (isset($change['field']) && $change['field'] === 'messages') {
                            $this->processWhatsAppMessage($change['value']);
                        }
                    }
                }
            }
        }

        return response('OK', 200);
    }

    private function processWhatsAppMessage($messageData)
    {
        try {
            // 1. Identificar la organización por phone_number_id
            $phoneNumberId = $messageData['metadata']['phone_number_id'] ?? null;
            $displayPhoneNumber = $messageData['metadata']['display_phone_number'] ?? null;
            
            $whatsappNumber = null;
            $organization = null;
            
            if ($phoneNumberId) {
                $whatsappNumber = WhatsAppPhoneNumber::where('phone_number_id', $phoneNumberId)
                    ->with('organization')
                    ->first();
                    
                if ($whatsappNumber) {
                    $organization = $whatsappNumber->organization;
                    $whatsappNumber->update(['last_used_at' => now()]);
                    
                    Log::info('🏢 Mensaje asociado a organización', [
                        'organization_id' => $organization->id,
                        'organization_name' => $organization->name,
                        'phone_number' => $whatsappNumber->phone_number,
                        'phone_number_id' => $phoneNumberId,
                    ]);
                } else {
                    Log::warning('⚠️ Número de WhatsApp no registrado en el sistema', [
                        'phone_number_id' => $phoneNumberId,
                        'display_phone_number' => $displayPhoneNumber,
                    ]);
                }
            }
            
            $messages = $messageData['messages'] ?? [];
            $contacts = $messageData['contacts'] ?? [];

            foreach ($messages as $message) {
                $messageId = $message['id'] ?? '';
                $fromNumber = $message['from'] ?? '';
                $timestamp = $message['timestamp'] ?? '';
                $messageType = $message['type'] ?? 'text';

                // Obtener nombre del perfil
                $profileName = '';
                foreach ($contacts as $contact) {
                    if ($contact['wa_id'] === $fromNumber) {
                        $profileName = $contact['profile']['name'] ?? '';
                        break;
                    }
                }

                // Procesar según el tipo de mensaje
                $processedData = $this->processMessageByType($message, $messageType);

                Log::info('WhatsApp message processed', [
                    'messageId' => $messageId,
                    'messageType' => $messageType,
                    'fromNumber' => $fromNumber,
                    'profileName' => $profileName,
                    'timestamp' => $timestamp,
                    'processedData' => $processedData
                ]);

                // Lógica de envío a n8n:
                // - Si NO hay intervención humana reciente → Enviar a n8n inmediatamente
                // - Si hay intervención humana reciente → Programar job para enviar después de 5 minutos
                
                // Buscar lead, priorizando por organización si está disponible
                $leadQuery = Lead::where('phone_number', $fromNumber);
                if ($organization) {
                    $leadQuery->where('organization_id', $organization->id);
                }
                $lead = $leadQuery->first();
                
                if ($lead && !$lead->shouldSendToN8n()) {
                    // Hay intervención humana reciente, programar job para enviar después de 5 minutos
                    $minutesSinceIntervention = now()->diffInMinutes($lead->last_human_intervention_at ?? now());
                    $delayMinutes = 5 - $minutesSinceIntervention;
                    
                    \App\Jobs\SendMessageToN8nJob::dispatch(
                        $lead->id,
                        $messageId,
                        $processedData,
                        $fromNumber,
                        $profileName,
                        $timestamp,
                        $messageType,
                        now()->toDateTimeString() // Timestamp de cuando se programó
                    )->delay(now()->addMinutes($delayMinutes));
                    
                    Log::info('⏸️ Mensaje programado para enviar a n8n después de 5 minutos', [
                        'organization_id' => $organization?->id,
                        'lead_id' => $lead->id,
                        'message_id' => $messageId,
                        'minutes_since_intervention' => $minutesSinceIntervention,
                        'will_send_after' => $delayMinutes . ' minutos',
                    ]);
                } else {
                    // No hay intervención humana reciente, enviar a n8n inmediatamente
                    // Si han pasado 20 minutos, re-habilitar bot automáticamente
                    if ($lead && $lead->bot_disabled && $lead->canBotRespond()) {
                        $lead->update(['bot_disabled' => false]);
                        Log::info('✅ Bot re-habilitado automáticamente (pasaron 20 min)', [
                            'lead_id' => $lead->id,
                        ]);
                    }
                    $this->sendToN8n(
                        $messageId, 
                        $processedData, 
                        $fromNumber, 
                        $profileName, 
                        $timestamp, 
                        $messageType,
                        $organization,
                        $whatsappNumber
                    );
                }
            }

            // Guardar mensaje del cliente automáticamente en la BD
            $this->saveClientMessage($messages, $contacts, $organization, $whatsappNumber);

            // Procesar con el servicio de leads
            $leadService = new WhatsAppLeadService();
            $results = $leadService->processWhatsAppMessage($messageData);

            Log::info('WhatsApp messages processed for lead detection', [
                'total_messages' => count($results),
                'high_value_leads' => count(array_filter($results, fn($r) => $r['isHighValueLead']))
            ]);

            // Log individual results
            foreach ($results as $result) {
                if ($result['isHighValueLead']) {
                    Log::info('🎯 High-value lead detected', [
                        'messageId' => $result['messageId'],
                        'leadScore' => $result['leadScore'],
                        'keywords' => $result['keywords']
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'data' => $messageData
            ]);
        }
    }

    private function processMessageByType($message, $messageType)
    {
        switch ($messageType) {
            case 'text':
                return [
                    'messageText' => $message['text']['body'] ?? '',
                    'contentType' => 'text',
                    'hasImage' => false,
                    'imageUrl' => null,
                    'imageId' => null
                ];

            case 'image':
                $imageData = $message['image'] ?? [];
                return [
                    'messageText' => 'Imagen enviada',
                    'contentType' => 'image',
                    'hasImage' => true,
                    'imageUrl' => $this->getImageUrl($imageData['id'] ?? ''),
                    'imageId' => $imageData['id'] ?? '',
                    'imageMimeType' => $imageData['mime_type'] ?? '',
                    'imageSha256' => $imageData['sha256'] ?? ''
                ];

            case 'video':
                $videoData = $message['video'] ?? [];
                return [
                    'messageText' => 'Video enviado',
                    'contentType' => 'video',
                    'hasImage' => false,
                    'imageUrl' => null,
                    'imageId' => null,
                    'videoId' => $videoData['id'] ?? '',
                    'videoMimeType' => $videoData['mime_type'] ?? ''
                ];

            case 'audio':
                $audioData = $message['audio'] ?? [];
                return [
                    'messageText' => 'Audio enviado',
                    'contentType' => 'audio',
                    'hasImage' => false,
                    'imageUrl' => null,
                    'imageId' => null,
                    'audioId' => $audioData['id'] ?? '',
                    'audioMimeType' => $audioData['mime_type'] ?? ''
                ];

            case 'document':
                $documentData = $message['document'] ?? [];
                return [
                    'messageText' => 'Documento enviado',
                    'contentType' => 'document',
                    'hasImage' => false,
                    'imageUrl' => null,
                    'imageId' => null,
                    'documentId' => $documentData['id'] ?? '',
                    'documentMimeType' => $documentData['mime_type'] ?? '',
                    'documentFilename' => $documentData['filename'] ?? ''
                ];

            default:
                return [
                    'messageText' => 'Mensaje no soportado',
                    'contentType' => 'unknown',
                    'hasImage' => false,
                    'imageUrl' => null,
                    'imageId' => null
                ];
        }
    }

    private function getImageUrl($imageId)
    {
        if (empty($imageId)) {
            return null;
        }

        // Construir URL para descargar la imagen desde WhatsApp Business API
        $accessToken = config('services.whatsapp.access_token');
        return "https://graph.facebook.com/v21.0/{$imageId}?access_token={$accessToken}";
    }

    /**
     * Guardar mensaje del cliente automáticamente en la BD
     */
    private function saveClientMessage(array $messages, array $contacts, ?Organization $organization = null, ?WhatsAppPhoneNumber $whatsappNumber = null): void
    {
        try {
            foreach ($messages as $message) {
                $messageId = $message['id'] ?? '';
                $fromNumber = $message['from'] ?? '';
                $messageText = $message['text']['body'] ?? '';
                
                if (empty($messageText) || empty($fromNumber)) {
                    continue;
                }

                // Buscar nombre del contacto
                $profileName = '';
                foreach ($contacts as $contact) {
                    if ($contact['wa_id'] === $fromNumber) {
                        $profileName = $contact['profile']['name'] ?? 'Desconocido';
                        break;
                    }
                }

                // Buscar usuario que tenga un lead con este número de teléfono
                $lead = Lead::where('phone_number', $fromNumber)->first();
                
                if ($lead && $lead->user_id) {
                    $user = \App\Models\User::find($lead->user_id);
                }
                
                // Si no hay usuario aún, buscar cualquier usuario activo (el sistema es multi-usuario pero para desarrollo usamos el primero)
                if (!$user) {
                    $user = \App\Models\User::first();
                }
                
                // Si aún no hay usuario, crear uno temporal o usar el usuario por defecto
                if (!$user) {
                    Log::warning('No hay usuarios en el sistema. Creando usuario temporal o usando el por defecto.', [
                        'fromNumber' => $fromNumber
                    ]);
                    // Intentar obtener el usuario por email por defecto
                    $user = \App\Models\User::where('email', 'alfredoromerox15@gmail.com')->first();
                }

                if (!$user) {
                    Log::warning('No se encontró usuario para guardar mensaje de WhatsApp', [
                        'fromNumber' => $fromNumber
                    ]);
                    continue;
                }

                // Crear o actualizar lead con organización
                $leadData = [
                    'client_name' => $profileName,
                    'intent' => 'consulta',
                    'lead_level' => 'cold',
                    'stage' => 'nuevo',
                    'confidence_score' => 0.0,
                ];
                
                if ($organization) {
                    $leadData['organization_id'] = $organization->id;
                }
                
                if ($whatsappNumber) {
                    $leadData['whatsapp_phone_number_id'] = $whatsappNumber->id;
                }
                
                $lead = Lead::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'phone_number' => $fromNumber,
                    ],
                    $leadData
                );

                // Verificar si el mensaje ya existe (evitar duplicados)
                $existingConversation = Conversation::where('message_id', $messageId)->first();
                
                if (!$existingConversation) {
                    // Guardar conversación del cliente con organización
                    $conversationData = [
                        'user_id' => $user->id,
                        'message_id' => $messageId,
                        'message_text' => $messageText,
                        'is_client_message' => true,
                        'is_employee' => false,
                        'platform' => 'whatsapp',
                        'timestamp' => date('Y-m-d H:i:s', $message['timestamp'] ?? time()),
                        'message_length' => strlen($messageText),
                    ];
                    
                    if ($organization) {
                        $conversationData['organization_id'] = $organization->id;
                    }
                    
                    if ($whatsappNumber) {
                        $conversationData['whatsapp_phone_number_id'] = $whatsappNumber->id;
                    }
                    
                    $lead->conversations()->create($conversationData);

                    Log::info('✅ Mensaje del cliente guardado automáticamente', [
                        'lead_id' => $lead->id,
                        'message_id' => $messageId,
                        'from' => $fromNumber,
                    ]);

                    // Verificar si el bot puede responder (han pasado 20 min desde intervención humana)
                    if ($lead->canBotRespond()) {
                        // Si han pasado 20 minutos, habilitar bot automáticamente
                        if ($lead->bot_disabled) {
                            $lead->update(['bot_disabled' => false]);
                            Log::info('✅ Bot re-habilitado automáticamente (pasaron 20 min)', [
                                'lead_id' => $lead->id,
                                'last_intervention' => $lead->last_human_intervention_at,
                            ]);
                        }
                        Log::info('📤 Mensaje listo para enviar a n8n', [
                            'lead_id' => $lead->id,
                            'bot_disabled' => false,
                        ]);
                    } else {
                        $minutesSinceIntervention = now()->diffInMinutes($lead->last_human_intervention_at ?? now());
                        Log::info('🤖 Bot deshabilitado (intervención humana reciente)', [
                            'lead_id' => $lead->id,
                            'phone_number' => $fromNumber,
                            'minutes_since_intervention' => $minutesSinceIntervention,
                            'bot_will_respond_after' => 20 - $minutesSinceIntervention . ' minutos',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error guardando mensaje del cliente automáticamente', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendToN8n($messageId, $processedData, $fromNumber, $profileName, $timestamp, $messageType, ?Organization $organization = null, ?WhatsAppPhoneNumber $whatsappNumber = null)
    {
        try {
            // Usar webhook específico de la organización si existe, sino el global
            $n8nUrl = $organization?->n8n_webhook_url ?? config('services.n8n.whatsapp_webhook_url');
            
            if (!$n8nUrl) {
                Log::warning('URL de n8n para WhatsApp no configurada');
                return;
            }

            // Buscar el lead para enviar su ID
            $lead = Lead::where('phone_number', $fromNumber)
                ->when($organization, fn($q) => $q->where('organization_id', $organization->id))
                ->first();
            
            $data = [
                'messageId' => $messageId,
                'messageText' => $processedData['messageText'],
                'fromNumber' => $fromNumber,
                'profileName' => $profileName,
                'timestamp' => $timestamp,
                'platform' => 'whatsapp',
                'messageType' => $messageType,
                'contentType' => $processedData['contentType'],
                'hasImage' => $processedData['hasImage'],
                'imageUrl' => $processedData['imageUrl'],
                'imageId' => $processedData['imageId'] ?? null,
                'imageMimeType' => $processedData['imageMimeType'] ?? null,
                'imageSha256' => $processedData['imageSha256'] ?? null,
                'videoId' => $processedData['videoId'] ?? null,
                'videoMimeType' => $processedData['videoMimeType'] ?? null,
                'audioId' => $processedData['audioId'] ?? null,
                'audioMimeType' => $processedData['audioMimeType'] ?? null,
                'documentId' => $processedData['documentId'] ?? null,
                'documentMimeType' => $processedData['documentMimeType'] ?? null,
                'documentFilename' => $processedData['documentFilename'] ?? null,
                // Datos multi-tenant
                'organizationId' => $organization?->id,
                'organizationName' => $organization?->name,
                'organizationSettings' => $organization?->settings,
                'leadId' => $lead?->id,
                // Credenciales del número específico o globales
                'accessToken' => $whatsappNumber?->getDecryptedToken() ?? config('services.whatsapp.access_token'),
                'phoneNumberId' => $whatsappNumber?->phone_number_id ?? config('services.whatsapp.phone_number_id'),
                'wabaId' => $whatsappNumber?->waba_id ?? null,
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('📤 Mensaje de WhatsApp enviado a n8n exitosamente', [
                    'organizationId' => $organization?->id,
                    'organizationName' => $organization?->name,
                    'messageId' => $messageId,
                    'messageType' => $messageType,
                    'hasImage' => $processedData['hasImage'],
                    'n8nUrl' => $n8nUrl,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Error enviando mensaje de WhatsApp a n8n', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error enviando mensaje de WhatsApp a n8n', [
                'error' => $e->getMessage(),
                'messageId' => $messageId
            ]);
        }
    }
}