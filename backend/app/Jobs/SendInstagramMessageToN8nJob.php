<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

#[Tries(1)]
#[Timeout(30)]
class SendInstagramMessageToN8nJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $senderId,
        public string $messageText,
        public string $messageId,
        public ?string $organizationId = null,
        public ?string $platform = 'instagram'
    ) {
    }

    public function handle(): void
    {
        try {
            // Try to send to brain service first
            $brainUrl = config('services.brain.url');
            
            if ($brainUrl) {
                $this->sendToBrain($brainUrl);
                return;
            }

            // Fallback to n8n if brain not configured
            $n8nUrl = config('services.n8n.webhook_url');
            if ($n8nUrl) {
                $this->sendToN8n($n8nUrl);
                return;
            }

            // Last resort: auto-reply directly via Meta API
            Log::warning('No brain or n8n configured, using auto-reply');
            $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
        } catch (\Exception $e) {
            Log::error('Error in SendInstagramMessageToN8nJob', [
                'error' => $e->getMessage(),
                'sender_id' => $this->senderId
            ]);
            
            // Try to send a simple auto-reply as fallback
            $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
        }
    }

    private function sendToBrain(string $brainUrl): void
    {
        try {
            $payload = [
                'organization_id' => $this->organizationId ?? 'default',
                'platform' => $this->platform,
                'customer_id' => $this->senderId,
                'customer_phone' => $this->senderId, // For IG it's the user ID
                'message' => $this->messageText,
                'conversation_history' => [],
                'thread_id' => null,
            ];

            $response = Http::timeout(30)->post("{$brainUrl}/api/webhook/chat", $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Message sent to brain successfully', [
                    'sender_id' => $this->senderId,
                    'reply' => $data['reply'] ?? 'no reply'
                ]);

                // Send the reply back to Instagram
                if (!empty($data['reply'])) {
                    $this->sendResponse($this->senderId, $data['reply']);
                }
            } else {
                Log::error('Brain returned error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                // Fallback to auto-reply
                $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
            }
        } catch (\Exception $e) {
            Log::error('Error sending to brain', [
                'error' => $e->getMessage(),
                'sender_id' => $this->senderId
            ]);
            throw $e;
        }
    }

    private function sendToN8n(string $n8nUrl): void
    {
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
            Log::info('Message sent to n8n successfully');
        } else {
            Log::error('Error sending to n8n', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            $this->sendResponse($this->senderId, $this->generateAutoReply($this->messageText));
        }
    }

    private function sendResponse(string $recipientId, string $messageText): void
    {
        $accessToken = config('services.instagram.access_token');
        $igUserId = config('services.instagram.ig_user_id');

        if (!$accessToken || !$igUserId) {
            Log::warning('No Instagram config', ['token' => !!$accessToken, 'ig_id' => !!$igUserId]);
            return;
        }

        try {
            // Use Instagram Graph API with Bearer token in header
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post("https://graph.instagram.com/v25.0/{$igUserId}/messages", [
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $messageText]
            ]);

            if ($response->successful()) {
                Log::info('Reply sent to Instagram user', [
                    'recipient' => $recipientId,
                    'message' => $messageText,
                    'response' => $response->body()
                ]);
            } else {
                Log::error('Error sending reply to Instagram', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending reply', ['error' => $e->getMessage()]);
        }
    }

    private function generateAutoReply(string $messageText): string
    {
        $message = strtolower(trim($messageText));

        // Check for common greetings
        if (strpos($message, 'hola') !== false || 
            strpos($message, 'hello') !== false || 
            strpos($message, 'hi') !== false ||
            strpos($message, 'buenas') !== false) {
            return "¡Hola! 👋Gracias por escribirnos. ¿En qué podemos ayudarte hoy?";
        }

        // Check for product inquiries
        if (strpos($message, 'precio') !== false || 
            strpos($message, 'costo') !== false ||
            strpos($message, 'cuanto') !== false) {
            return "¡Con gusto! Para darte información precisa sobre precios, necesitamos saber qué producto o servicio te interesa. ¿Nos puedes dar más detalles?";
        }

        // Check for contact/information requests
        if (strpos($message, 'contacto') !== false || 
            strpos($message, 'informacion') !== false ||
            strpos($message, 'info') !== false) {
            return "Puedes llamarnos al +58XXX-XXXXXXX o escribirnos aquí. Un agente te atenderá pronto.";
        }

        // Default response
        return "Gracias por tu mensaje. Un agente te contactará en breve para atenderte personalmente. 😊";
    }
}