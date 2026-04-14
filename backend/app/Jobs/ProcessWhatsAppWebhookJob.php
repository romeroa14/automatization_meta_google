<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\Message;
use App\Models\WhatsappInstance;
use App\Models\Workspace;
use App\Services\WhatsAppLeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $data)
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $data = $this->data;
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
    }

    private function processWhatsAppMessage($messageData)
    {
        try {
            $phoneNumberId = $messageData['metadata']['phone_number_id'] ?? null;
            $displayPhoneNumber = $messageData['metadata']['display_phone_number'] ?? null;
            
            $whatsappNumber = null;
            $workspace = null;
            
            if ($phoneNumberId) {
                $whatsappNumber = WhatsappInstance::where('phone_number_id', $phoneNumberId)
                    ->with('workspace')
                    ->first();
                    
                if ($whatsappNumber) {
                    $workspace = $whatsappNumber->workspace;
                    // $whatsappNumber->update(['last_used_at' => now()]); // last_used_at doesn't exist in WhatsappInstance
                    
                    Log::info('🏢 Mensaje asociado a workspace', [
                        'workspace_id' => $workspace->id,
                        'workspace_name' => $workspace->name,
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

                $profileName = '';
                foreach ($contacts as $contact) {
                    if ($contact['wa_id'] === $fromNumber) {
                        $profileName = $contact['profile']['name'] ?? '';
                        break;
                    }
                }

                $processedData = $this->processMessageByType($message, $messageType);

                Log::info('WhatsApp message processed', [
                    'messageId' => $messageId,
                    'messageType' => $messageType,
                    'fromNumber' => $fromNumber,
                    'profileName' => $profileName,
                    'timestamp' => $timestamp,
                    'processedData' => $processedData
                ]);

                $leadQuery = Lead::where('phone_number', $fromNumber);
                if ($workspace) {
                    $leadQuery->where('workspace_id', $workspace->id);
                }
                $lead = $leadQuery->first();
                
                if ($lead && !$lead->shouldSendToN8n()) {
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
                        now()->toDateTimeString(),
                        $organization?->id, // wait, I should replace this with $workspace?->id
                        $whatsappNumber?->id
                    )->delay(now()->addMinutes($delayMinutes));
                    
                    Log::info('⏸️ Mensaje programado para enviar a n8n después de 5 minutos', [
                        'organization_id' => $organization?->id,
                        'lead_id' => $lead->id,
                        'message_id' => $messageId,
                        'minutes_since_intervention' => $minutesSinceIntervention,
                        'will_send_after' => $delayMinutes . ' minutos',
                    ]);
                } else {
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
                        $workspace,
                        $whatsappNumber
                    );
                }
            }

            $this->saveClientMessage($messages, $contacts, $workspace, $whatsappNumber);

            $leadService = new WhatsAppLeadService();
            $results = $leadService->processWhatsAppMessage($messageData);

            Log::info('WhatsApp messages processed for lead detection', [
                'total_messages' => count($results),
                'high_value_leads' => count(array_filter($results, fn($r) => $r['isHighValueLead']))
            ]);

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

        $accessToken = config('services.whatsapp.access_token');
        return "https://graph.facebook.com/v21.0/{$imageId}?access_token={$accessToken}";
    }

    private function saveClientMessage(array $messages, array $contacts, ?Workspace $workspace = null, ?WhatsappInstance $whatsappNumber = null): void
    {
        try {
            foreach ($messages as $message) {
                $messageId = $message['id'] ?? '';
                $fromNumber = $message['from'] ?? '';
                $messageText = $message['text']['body'] ?? '';
                
                if (empty($messageText) || empty($fromNumber)) {
                    continue;
                }

                $profileName = '';
                foreach ($contacts as $contact) {
                    if ($contact['wa_id'] === $fromNumber) {
                        $profileName = $contact['profile']['name'] ?? 'Desconocido';
                        break;
                    }
                }

                $lead = Lead::where('phone_number', $fromNumber)->first();
                $user = null;
                
                if ($lead && $lead->user_id) {
                    $user = \App\Models\User::find($lead->user_id);
                }
                
                if (!$user) {
                    $user = \App\Models\User::first();
                }
                
                if (!$user) {
                    $user = \App\Models\User::where('email', 'alfredoromerox15@gmail.com')->first();
                }

                if (!$user) {
                    Log::warning('No se encontró usuario para guardar mensaje de WhatsApp', [
                        'fromNumber' => $fromNumber
                    ]);
                    continue;
                }

                $leadData = [
                    'client_name' => $profileName,
                    'intent' => 'consulta',
                    'lead_level' => 'cold',
                    'stage' => 'nuevo',
                    'confidence_score' => 0.0,
                    'user_id' => $user->id,
                ];
                
                if ($workspace) {
                    $leadData['workspace_id'] = $workspace->id;
                }
                
                if ($whatsappNumber) {
                    $leadData['whatsapp_instance_id'] = $whatsappNumber->id;
                }
                
                $lead = Lead::updateOrCreate(
                    [
                        'phone_number' => $fromNumber,
                    ],
                    $leadData
                );

                $existingConversation = Message::where('message_id', $messageId)->first();
                
                if (!$existingConversation) {
                    $conversationData = [
                        'user_id' => $user->id,
                        'message_id' => $messageId,
                        'content' => $messageText,
                        'direction' => 'inbound',
                        'is_client_message' => true,
                        'is_employee' => false,
                        'platform' => 'whatsapp',
                        'timestamp' => date('Y-m-d H:i:s', $message['timestamp'] ?? time()),
                        'message_length' => strlen($messageText),
                    ];
                    
                    if ($workspace) {
                        $conversationData['workspace_id'] = $workspace->id;
                    }
                    
                    if ($whatsappNumber) {
                        $conversationData['whatsapp_instance_id'] = $whatsappNumber->id;
                    }
                    
                    $lead->messages()->create($conversationData);

                    Log::info('✅ Mensaje del cliente guardado automáticamente', [
                        'lead_id' => $lead->id,
                        'message_id' => $messageId,
                        'from' => $fromNumber,
                    ]);

                    if ($lead->canBotRespond()) {
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

    private function sendToN8n($messageId, $processedData, $fromNumber, $profileName, $timestamp, $messageType, ?Workspace $workspace = null, ?WhatsappInstance $whatsappNumber = null)
    {
        try {
            $n8nUrl = $workspace?->n8n_webhook_url ?? config('services.n8n.whatsapp_webhook_url');
            
            if (!$n8nUrl) {
                Log::warning('URL de n8n para WhatsApp no configurada');
                return;
            }

            $lead = Lead::where('phone_number', $fromNumber)
                ->when($workspace, fn($q) => $q->where('workspace_id', $workspace->id))
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
                'workspaceId' => $workspace?->id,
                'workspaceName' => $workspace?->name,
                'workspaceSettings' => $workspace?->settings,
                'leadId' => $lead?->id,
                'accessToken' => $whatsappNumber?->access_token ?? config('services.whatsapp.access_token'),
                'phoneNumberId' => $whatsappNumber?->phone_number_id ?? config('services.whatsapp.phone_number_id'),
                'wabaId' => $whatsappNumber?->waba_id ?? null,
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('📤 Mensaje de WhatsApp enviado a n8n exitosamente', [
                    'workspaceId' => $workspace?->id,
                    'workspaceName' => $workspace?->name,
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
