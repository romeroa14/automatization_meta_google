<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendInstagramMessageToN8nJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 30;

    public function __construct(
        public string $senderId,
        public string $messageText,
        public string $messageId
    ) {
    }

    public function handle(): void
    {
        try {
            $n8nUrl = config('services.n8n.webhook_url');

            if (!$n8nUrl) {
                Log::warning('URL de n8n no configurada, usando respuesta automática (Job)');
                $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
                return;
            }

            $data = [
                'sender_id' => $this->senderId,
                'message' => $this->messageText,
                'message_id' => $this->messageId,
                'access_token' => config('services.instagram.access_token'),
                'timestamp' => now()->toISOString(),
                'platform' => 'instagram'
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('Mensaje enviado a n8n exitosamente desde Job', [
                    'sender_id' => $this->senderId,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Error enviando a n8n desde Job', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
            }
        } catch (\Exception $e) {
            Log::error('Error enviando a n8n desde Job', [
                'error' => $e->getMessage(),
                'sender_id' => $this->senderId
            ]);

            $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
        }
    }

    private function sendResponse($senderId, $messageText)
    {
        $accessToken = config('services.instagram.access_token');

        if (!$accessToken) {
            return;
        }

        Http::post("https://graph.facebook.com/v18.0/me/messages", [
            'recipient' => ['id' => $senderId],
            'message' => ['text' => $messageText],
            'access_token' => $accessToken
        ]);
    }

    private function generateAutoReply($messageText)
    {
        $message = strtolower(trim($messageText));

        if (strpos($message, 'hola') !== false || strpos($message, 'hi') !== false) {
            return '¡Hola! 👋 Bienvenido a Admetricas. ¿En qué puedo ayudarte hoy?';
        }

        return '🤖 Gracias por tu mensaje. Un agente humano te responderá pronto.';
    }
}
