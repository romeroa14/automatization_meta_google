<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProcessInstagramWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $data)
    {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        $data = $this->data;
        
        try {
            Log::info('🔍 DEBUG: Analizando estructura de datos en JOB', [
                'has_entry' => isset($data['entry']),
                'has_field' => isset($data['field']),
                'data_structure' => array_keys($data)
            ]);

            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entryIndex => $entry) {
                    if (isset($entry['messaging'])) {
                        foreach ($entry['messaging'] as $messaging) {
                            $this->processInstagramMessage($messaging);
                        }
                    }

                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $changeIndex => $change) {
                            if (isset($change['field']) && $change['field'] === 'messages') {
                                $this->processTestMessage($change['value']);
                            }
                            
                            if (isset($change['field']) && $change['field'] === 'comments') {
                                $this->processInstagramComment($change['value']);
                            }
                        }
                    }
                }
            }
            elseif (isset($data['field']) && $data['field'] === 'messages') {
                $this->processTestMessage($data['value']);
            }
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de Instagram en JOB', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    private function processTestMessage($testData)
    {
        $senderId = $testData['sender']['id'] ?? null;
        $message = $testData['message'] ?? null;

        if (!$senderId || !$message) {
            Log::warning('Datos de prueba incompletos', ['data' => $testData]);
            return;
        }

        $messageText = $message['text'] ?? '';
        $messageId = $message['mid'] ?? '';

        Log::info('Mensaje de prueba de Facebook procesado', [
            'sender_id' => $senderId,
            'message_text' => $messageText,
            'message_id' => $messageId,
            'test_data' => $testData
        ]);

        $this->processInstagramMessage([
            'sender' => ['id' => $senderId],
            'message' => $message,
            'timestamp' => $testData['timestamp'] ?? time()
        ]);
    }

    private function processInstagramMessage($messaging)
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $message = $messaging['message'] ?? null;

        if (!$senderId || !$message) {
            return;
        }

        $messageText = $message['text'] ?? '';
        $messageId = $message['mid'] ?? '';

        Log::info('Mensaje de Instagram procesado', [
            'sender_id' => $senderId,
            'message_text' => $messageText,
            'message_id' => $messageId
        ]);

        if (empty($messageText)) {
            Log::info('Mensaje no es de texto, ignorando');
            return;
        }

        sleep(rand(2, 5));
        
        \App\Jobs\SendInstagramMessageToN8nJob::dispatch(
            $senderId,
            $messageText,
            $messageId
        )->onQueue('webhooks');
    }

    private function processInstagramComment($commentData)
    {
        try {
            $commentId = $commentData['id'] ?? '';
            $commentText = $commentData['text'] ?? '';
            $commenterId = $commentData['from']['id'] ?? '';
            $commenterUsername = $commentData['from']['username'] ?? '';
            $mediaId = $commentData['media']['id'] ?? '';

            $n8nUrl = config('services.n8n.comments_webhook_url');

            if (!$n8nUrl) {
                Log::warning('URL de n8n para comentarios no configurada');
                return;
            }

            $data = [
                'comment_id' => $commentId,
                'comment_text' => $commentText,
                'commenter_id' => $commenterId,
                'commenter_username' => $commenterUsername,
                'media_id' => $mediaId,
                'access_token' => config('services.instagram.access_token'),
                'timestamp' => now()->toISOString(),
                'platform' => 'instagram_comments'
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('Comentario enviado a n8n exitosamente', [
                    'comment_id' => $commentId,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Error enviando comentario a n8n', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error procesando comentario', [
                'error' => $e->getMessage(),
                'data' => $commentData
            ]);
        }
    }
}
