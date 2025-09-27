<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InstagramWebhookController extends Controller
{
    /**
     * Verificar webhook de Instagram (GET)
     * Meta envía un challenge para verificar la URL
     */
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Token de verificación configurado en Meta
        $verifyToken = config('services.instagram.verify_token', 'adsbot');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Instagram webhook verificado exitosamente');
            return response($challenge, 200);
        }

        Log::warning('Instagram webhook verificación fallida', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $verifyToken
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Manejar webhook de Instagram (POST)
     * Recibe mensajes y eventos de Instagram
     */
    public function handleWebhook(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('Instagram webhook recibido', [
                'data' => $data,
                'headers' => $request->headers->all()
            ]);

            // Verificar si es un mensaje de Instagram (formato estándar)
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['messaging'])) {
                        foreach ($entry['messaging'] as $messaging) {
                            $this->processInstagramMessage($messaging);
                        }
                    }
                }
            }
            // Verificar si es un mensaje de prueba de Facebook (formato de prueba)
            elseif (isset($data['field']) && $data['field'] === 'messages') {
                $this->processTestMessage($data['value']);
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de Instagram', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Procesar mensaje de prueba de Facebook
     */
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

        // Procesar como mensaje normal
        $this->processInstagramMessage([
            'sender' => ['id' => $senderId],
            'message' => $message,
            'timestamp' => $testData['timestamp'] ?? time()
        ]);
    }

    /**
     * Procesar mensaje de Instagram
     */
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

        // 1️⃣ Validación inicial - Solo procesar mensajes de texto
        if (empty($messageText)) {
            Log::info('Mensaje no es de texto, ignorando');
            return;
        }

        // 2️⃣ Delay humano (2-5 segundos)
        sleep(rand(2, 5));

        // 3️⃣ Enviar a n8n para procesamiento
        $this->sendToN8n($senderId, $messageText, $messageId);
    }

    /**
     * Enviar mensaje a n8n para procesamiento
     */
    private function sendToN8n($senderId, $messageText, $messageId)
    {
        try {
            $n8nUrl = config('services.n8n.webhook_url');
            
            if (!$n8nUrl) {
                Log::warning('URL de n8n no configurada, usando respuesta automática');
                $this->sendResponse($senderId, $this->generateAutoReply($messageText));
                return;
            }

            $data = [
                'sender_id' => $senderId,
                'message' => $messageText,
                'message_id' => $messageId,
                'timestamp' => now()->toISOString(),
                'platform' => 'instagram'
            ];

            $response = Http::post($n8nUrl, $data);

            if ($response->successful()) {
                Log::info('Mensaje enviado a n8n exitosamente', [
                    'sender_id' => $senderId,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Error enviando a n8n', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                // Fallback a respuesta automática
                $this->sendResponse($senderId, $this->generateAutoReply($messageText));
            }

        } catch (\Exception $e) {
            Log::error('Error enviando a n8n', [
                'error' => $e->getMessage(),
                'sender_id' => $senderId
            ]);
            
            // Fallback a respuesta automática
            $this->sendResponse($senderId, $this->generateAutoReply($messageText));
        }
    }

    /**
     * Procesar mensaje con IA
     */
    private function processWithAI($senderId, $messageText, $messageId)
    {
        try {
            // 4️⃣ Consulta a Base de Datos / Google Sheets
            $planData = $this->getPlanData($messageText);
            
            // 5️⃣ Procesamiento con IA
            $aiResponse = $this->getAIResponse($messageText, $planData);
            
            // 6️⃣ Construcción de Respuesta
            $finalResponse = $this->buildFinalResponse($aiResponse, $planData);
            
            // 7️⃣ Respuesta en Instagram
            $this->sendResponse($senderId, $finalResponse);
            
            // 8️⃣ Registro en CRM / Google Sheets
            $this->logToCRM($senderId, $messageText, $finalResponse);
            
        } catch (\Exception $e) {
            Log::error('Error procesando con IA', [
                'error' => $e->getMessage(),
                'sender_id' => $senderId,
                'message' => $messageText
            ]);
            
            // Fallback a respuesta simple
            $this->sendResponse($senderId, '🤖 Gracias por tu mensaje. Un agente humano te responderá pronto.');
        }
    }

    /**
     * 4️⃣ Consulta a Base de Datos / Google Sheets
     */
    private function getPlanData($messageText)
    {
        $message = strtolower($messageText);
        
        // Buscar planes en la base de datos
        $plans = \App\Models\AdvertisingPlan::where('status', 'active')->get();
        
        $matchedPlan = null;
        foreach ($plans as $plan) {
            if (strpos($message, strtolower($plan->name)) !== false || 
                strpos($message, strtolower($plan->description)) !== false) {
                $matchedPlan = $plan;
                break;
            }
        }
        
        return $matchedPlan;
    }

    /**
     * 5️⃣ Procesamiento con IA
     */
    private function getAIResponse($messageText, $planData)
    {
        $geminiApiKey = config('services.gemini.api_key');
        
        if (!$geminiApiKey) {
            return $this->generateAutoReply($messageText);
        }

        $context = "Eres el asistente de Admetricas, una empresa de marketing digital. ";
        if ($planData) {
            $context .= "Plan disponible: {$planData->name} - {$planData->description} - Precio: \${$planData->total_budget}";
        }
        $context .= " Responde breve, clara y en tono humano. Guía al cliente a WhatsApp o a comprar directamente.";

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$geminiApiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $context . "\n\nMensaje del cliente: " . $messageText]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? $this->generateAutoReply($messageText);
            }
        } catch (\Exception $e) {
            Log::error('Error con IA', ['error' => $e->getMessage()]);
        }

        return $this->generateAutoReply($messageText);
    }

    /**
     * 6️⃣ Construcción de Respuesta
     */
    private function buildFinalResponse($aiResponse, $planData)
    {
        $response = $aiResponse;
        
        if ($planData) {
            $response .= "\n\n💰 **{$planData->name}** - \${$planData->total_budget}";
            $response .= "\n📝 {$planData->description}";
        }
        
        $response .= "\n\n👉 Escríbenos a WhatsApp para reservar: https://wa.me/584241234567";
        
        return $response;
    }

    /**
     * 7️⃣ Respuesta en Instagram
     */
    private function sendResponse($senderId, $messageText)
    {
        $accessToken = config('services.instagram.access_token');
        
        if (!$accessToken) {
            Log::warning('Token de acceso de Instagram no configurado');
            return;
        }

        $response = Http::post("https://graph.facebook.com/v18.0/me/messages", [
            'recipient' => ['id' => $senderId],
            'message' => ['text' => $messageText],
            'access_token' => $accessToken
        ]);

        if ($response->successful()) {
            Log::info('Respuesta enviada', [
                'sender_id' => $senderId,
                'message' => $messageText
            ]);
        } else {
            Log::error('Error enviando respuesta', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);
        }
    }

    /**
     * 8️⃣ Registro en CRM / Google Sheets
     */
    private function logToCRM($senderId, $userMessage, $botResponse)
    {
        try {
            // Crear registro en la base de datos
            \App\Models\TelegramConversation::create([
                'user_id' => $senderId,
                'platform' => 'instagram',
                'user_message' => $userMessage,
                'bot_response' => $botResponse,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Conversación registrada en CRM', [
                'sender_id' => $senderId,
                'user_message' => $userMessage
            ]);
        } catch (\Exception $e) {
            Log::error('Error registrando en CRM', [
                'error' => $e->getMessage(),
                'sender_id' => $senderId
            ]);
        }
    }

    /**
     * Generar respuesta automática basada en el mensaje
     */
    private function generateAutoReply($messageText)
    {
        $message = strtolower(trim($messageText));

        if (strpos($message, 'hola') !== false || strpos($message, 'hi') !== false) {
            return '¡Hola! 👋 Bienvenido a Admetricas. ¿En qué puedo ayudarte hoy?';
        }

        if (strpos($message, 'precio') !== false || strpos($message, 'costo') !== false) {
            return '💰 Para información sobre precios, visita nuestro sitio web: https://admetricas.com';
        }

        if (strpos($message, 'contacto') !== false || strpos($message, 'telefono') !== false) {
            return '📞 Puedes contactarnos en: info@admetricas.com';
        }

        if (strpos($message, 'servicio') !== false || strpos($message, 'ayuda') !== false) {
            return '🛠️ Ofrecemos servicios de marketing digital y automatización. ¿Te interesa algún servicio específico?';
        }

        return '🤖 Gracias por tu mensaje. Un agente humano te responderá pronto. Mientras tanto, visita https://admetricas.com para más información.';
    }

    /**
     * Verificar webhook de n8n (GET)
     */
    public function verifyN8nWebhook(Request $request)
    {
        $challenge = $request->query('challenge');
        
        if ($challenge) {
            Log::info('Webhook de n8n verificado', ['challenge' => $challenge]);
            return response($challenge, 200);
        }

        return response('OK', 200);
    }

    /**
     * Manejar webhook de n8n (conexión real)
     */
    public function handleN8nWebhook(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('Webhook de n8n recibido', [
                'data' => $data,
                'headers' => $request->headers->all()
            ]);

            // Procesar datos de n8n
            if (isset($data['sender_id']) && isset($data['message'])) {
                $this->sendResponse($data['sender_id'], $data['message']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook de n8n procesado',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de n8n', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error procesando webhook'
            ], 500);
        }
    }
}