<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Message;
use App\Models\WhatsappInstance;
use App\Models\Workspace;
use App\Jobs\ProcessWhatsAppWebhookJob;
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
            $whatsappNumber = WhatsappInstance::where('webhook_verify_token', $token)->first();
            
            if ($whatsappNumber) {
                Log::info('WhatsApp webhook verified successfully', [
                    'workspace_id' => $whatsappNumber->workspace_id,
                    'phone_number' => $whatsappNumber->phone_number,
                ]);
                return response($challenge, 200);
            }
            
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
        
        Log::info('WhatsApp webhook received (async dispatch)', [
            'has_entry' => isset($data['entry'])
        ]);

        if (isset($data['entry']) && is_array($data['entry'])) {
            ProcessWhatsAppWebhookJob::dispatch($data)->onQueue('webhooks');
        }

        return response('OK', 200);
    }

    public function handleN8nResponse(Request $request)
    {
        try {
            $data = $request->all();
            
            Log::info('🤖 Respuesta de n8n recibida para loguear', $data);

            $leadId = $data['leadId'] ?? null;
            $messageText = $data['responseText'] ?? ($data['message'] ?? '');
            
            if (!$leadId || empty($messageText)) {
                return response()->json(['error' => 'Missing data'], 400);
            }

            $lead = Lead::findOrFail($leadId);
            
            $whatsappNumber = $lead->whatsapp_instance_id 
                ? WhatsappInstance::find($lead->whatsapp_instance_id)
                : ($lead->workspace_id ? Workspace::find($lead->workspace_id)?->whatsappInstances()->where('is_default', true)->first() : null);

            if (!$whatsappNumber) {
                $phoneNumberId = config('services.whatsapp.phone_number_id');
                $accessToken = config('services.whatsapp.access_token');
            } else {
                $phoneNumberId = $whatsappNumber->phone_number_id;
                $accessToken = $whatsappNumber->access_token;
            }

            if (!$phoneNumberId || !$accessToken) {
                return response()->json(['error' => 'No de WhatsApp credentials found'], 400);
            }

            $to = preg_replace('/[^0-9]/', '', $lead->phone_number);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $messageText,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('❌ Error enviando respuesta de BOT desde CRM', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['error' => 'Meta API Error', 'details' => $response->json()], 502);
            }

            $responseData = $response->json();
            $wamid = $responseData['messages'][0]['id'] ?? null;

            $message = $lead->messages()->create([
                'user_id' => $lead->user_id,
                'workspace_id' => $lead->workspace_id,
                'whatsapp_instance_id' => $whatsappNumber?->id,
                'message_id' => $wamid,
                'content' => $messageText,
                'direction' => 'outbound',
                'is_client_message' => false,
                'is_employee' => false,
                'platform' => 'whatsapp',
                'timestamp' => now()->toDateTimeString(),
                'status' => 'sent',
                'message_length' => strlen($messageText),
            ]);

            Log::info('✅ Respuesta de BOT enviada y logueada', [
                'lead_id' => $lead->id,
                'wamid' => $wamid,
            ]);

            return response()->json([
                'success' => true,
                'message_id' => $message->id,
                'wamid' => $wamid,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error guardando respuesta de n8n', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
