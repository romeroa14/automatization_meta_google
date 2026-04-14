<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInstagramWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InstagramWebhookController extends Controller
{
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.instagram.verify_token', 'adsbot');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Instagram webhook verificado exitosamente');
            return response($challenge, 200);
        }

        if ($mode === null && $token === null) {
            Log::info('Webhook de Instagram recibido (no es verificación)');
            return response('OK', 200);
        }

        Log::warning('Instagram webhook verificación fallida', [
            'mode' => $mode,
            'token' => $token,
            'expected_token' => $verifyToken
        ]);

        return response('Forbidden', 403);
    }

    public function handleCommentsWebhook(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('Instagram comments webhook recibido (async dispatch)', [
                'data' => $data
            ]);

            if (isset($data['entry'])) {
                // Despachamos el mismo job, el job ya tiene lógica para comments
                ProcessInstagramWebhookJob::dispatch($data)->onQueue('webhooks');
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error procesando webhook de comentarios', [
                'error' => $e->getMessage()
            ]);
            return response('Error', 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('Instagram webhook recibido (async dispatch)', [
                'has_entry' => isset($data['entry'])
            ]);

            if (isset($data['entry']) || isset($data['field'])) {
                ProcessInstagramWebhookJob::dispatch($data)->onQueue('webhooks');
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

    public function verifyN8nWebhook(Request $request)
    {
        $challenge = $request->query('challenge');

        if ($challenge) {
            Log::info('Webhook de n8n verificado', ['challenge' => $challenge]);
            return response($challenge, 200);
        }

        return response('OK', 200);
    }

    public function handleN8nWebhook(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('Webhook de n8n recibido', [
                'data' => $data,
                'headers' => $request->headers->all()
            ]);

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
            Log::info('Respuesta enviada desde n8n webhook handler', [
                'sender_id' => $senderId,
                'message' => $messageText
            ]);
        } else {
            Log::error('Error enviando respuesta desde n8n webhook handler', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);
        }
    }
}
