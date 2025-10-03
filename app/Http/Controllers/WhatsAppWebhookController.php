<?php

namespace App\Http\Controllers;

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
            // Extraer datos del mensaje
            $messages = $messageData['messages'] ?? [];
            $contacts = $messageData['contacts'] ?? [];

            foreach ($messages as $message) {
                $messageId = $message['id'] ?? '';
                $messageText = $message['text']['body'] ?? '';
                $fromNumber = $message['from'] ?? '';
                $timestamp = $message['timestamp'] ?? '';

                // Buscar nombre del contacto
                $profileName = '';
                foreach ($contacts as $contact) {
                    if ($contact['wa_id'] === $fromNumber) {
                        $profileName = $contact['profile']['name'] ?? '';
                        break;
                    }
                }

                Log::info('WhatsApp message processed', [
                    'messageId' => $messageId,
                    'messageText' => $messageText,
                    'fromNumber' => $fromNumber,
                    'profileName' => $profileName,
                    'timestamp' => $timestamp
                ]);

                // Enviar a n8n
                $this->sendToN8n($messageId, $messageText, $fromNumber, $profileName, $timestamp);
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'data' => $messageData
            ]);
        }
    }

    private function sendToN8n($messageId, $messageText, $fromNumber, $profileName, $timestamp)
    {
        try {
            $n8nUrl = config('services.n8n.whatsapp_webhook_url');
            
            if (!$n8nUrl) {
                Log::warning('URL de n8n para WhatsApp no configurada');
                return;
            }

            $data = [
                'messageId' => $messageId,
                'messageText' => $messageText,
                'fromNumber' => $fromNumber,
                'profileName' => $profileName,
                'timestamp' => $timestamp,
                'accessToken' => config('services.whatsapp.access_token'),
                'phoneNumberId' => config('services.whatsapp.phone_number_id'),
                'platform' => 'whatsapp'
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('Mensaje de WhatsApp enviado a n8n exitosamente', [
                    'messageId' => $messageId,
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