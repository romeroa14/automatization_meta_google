<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppLeadService;
use App\Models\Lead;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verifyWebhook(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::info('WhatsApp webhook verification attempt', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $verifyToken
        ]);

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed');
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

                // Enviar a n8n solo si el bot NO está deshabilitado para este lead
                $lead = Lead::where('phone_number', $fromNumber)->first();
                if ($lead && $lead->bot_disabled) {
                    Log::info('🤖 Bot deshabilitado, no se envía mensaje a n8n', [
                        'lead_id' => $lead->id,
                        'message_id' => $messageId,
                    ]);
                } else {
                    $this->sendToN8n($messageId, $processedData, $fromNumber, $profileName, $timestamp, $messageType);
                }
            }

            // Guardar mensaje del cliente automáticamente en la BD
            $this->saveClientMessage($messages, $contacts);

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
    private function saveClientMessage(array $messages, array $contacts): void
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
                } else {
                    // Si no hay lead, buscar usuario por número de WhatsApp configurado
                    $user = \App\Models\User::where('whatsapp_number', config('services.whatsapp.phone_number_id'))->first();
                    
                    // Si aún no hay usuario, usar el primero (para desarrollo)
                    if (!$user) {
                        $user = \App\Models\User::first();
                    }
                }

                if (!$user) {
                    Log::warning('No se encontró usuario para guardar mensaje de WhatsApp', [
                        'fromNumber' => $fromNumber
                    ]);
                    continue;
                }

                // Crear o actualizar lead
                $lead = Lead::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'phone_number' => $fromNumber,
                    ],
                    [
                        'client_name' => $profileName,
                        'intent' => 'consulta',
                        'lead_level' => 'cold',
                        'stage' => 'nuevo',
                        'confidence_score' => 0.0,
                    ]
                );

                // Verificar si el mensaje ya existe (evitar duplicados)
                $existingConversation = Conversation::where('message_id', $messageId)->first();
                
                if (!$existingConversation) {
                    // Guardar conversación del cliente
                    $lead->conversations()->create([
                        'user_id' => $user->id,
                        'message_id' => $messageId,
                        'message_text' => $messageText,
                        'is_client_message' => true,
                        'is_employee' => false,
                        'platform' => 'whatsapp',
                        'timestamp' => date('Y-m-d H:i:s', $message['timestamp'] ?? time()),
                        'message_length' => strlen($messageText),
                    ]);

                    Log::info('✅ Mensaje del cliente guardado automáticamente', [
                        'lead_id' => $lead->id,
                        'message_id' => $messageId,
                        'from' => $fromNumber,
                    ]);

                    // Enviar a n8n solo si el bot NO está deshabilitado
                    if (!$lead->bot_disabled) {
                        // El envío a n8n se hace en processWhatsAppMessage, aquí solo guardamos
                        Log::info('📤 Mensaje listo para enviar a n8n', [
                            'lead_id' => $lead->id,
                            'bot_disabled' => false,
                        ]);
                    } else {
                        Log::info('🤖 Bot deshabilitado para este lead, no se enviará a n8n', [
                            'lead_id' => $lead->id,
                            'phone_number' => $fromNumber,
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

    private function sendToN8n($messageId, $processedData, $fromNumber, $profileName, $timestamp, $messageType)
    {
        try {
            $n8nUrl = config('services.n8n.whatsapp_webhook_url');
            
            if (!$n8nUrl) {
                Log::warning('URL de n8n para WhatsApp no configurada');
                return;
            }

            $data = [
                'messageId' => $messageId,
                'messageText' => $processedData['messageText'],
                'fromNumber' => $fromNumber,
                'profileName' => $profileName,
                'timestamp' => $timestamp,
                'accessToken' => config('services.whatsapp.access_token'),
                'phoneNumberId' => config('services.whatsapp.phone_number_id'),
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
                'documentFilename' => $processedData['documentFilename'] ?? null
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('Mensaje de WhatsApp enviado a n8n exitosamente', [
                    'messageId' => $messageId,
                    'messageType' => $messageType,
                    'hasImage' => $processedData['hasImage'],
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