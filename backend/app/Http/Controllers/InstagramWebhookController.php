<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\ChatbotConfig;
use App\Models\WebhookLog;
use App\Services\TenantManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class InstagramWebhookController extends Controller
{
    /**
     * Instagram Webhook Verification (GET)
     */
    public function verify(Request $request, string $slug = null)
    {
        // Si no hay slug, usar tenant hardcoded
        if (!$slug) {
            $slug = 'ads_vnzla';
        }
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        
        // Find tenant by slug
        $tenant = Tenant::findBySlug($slug);
        
        if (!$tenant) {
            return response('Tenant not found', 404);
        }
        
        // Verify token matches
        $verifyToken = $tenant->webhook_secret ?? config('services.instagram.verify_token', 'adsbot');
        
        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info("Instagram webhook verified for tenant: {$slug}");
            return response($challenge, 200);
        }
        
        return response('Forbidden', 403);
    }

    /**
     * Handle Instagram Webhook (POST) - Multi-tenant with slug
     */
    public function handle(Request $request, string $slug = null)
    {
        // Si no hay slug, usar tenant hardcoded (legacy/mi cuenta)
        if (!$slug) {
            $slug = 'ads_vnzla'; // Tu cuenta actual
        }
        
        $tenant = Tenant::findBySlug($slug);
        
        if (!$tenant) {
            Log::error("Instagram webhook: Tenant not found", ['slug' => $slug]);
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        
        // Set tenant context
        TenantManager::setCurrentTenant($tenant);
        
        // Log the webhook
        // $this->logWebhook($tenant, 'instagram', 'incoming', $request->all());
        
        $data = $request->all();
        
        // Handle webhook verification
        if (isset($data['object']) && $data['object'] === 'instagram') {
            return $this->handleInstagramEntry($tenant, $data);
        }
        
        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle incoming Instagram messages
     */
    private function handleInstagramEntry(Tenant $tenant, array $data)
    {
        try {
            foreach ($data['entry'] ?? [] as $entry) {
                $instagramAccountId = $entry['id'] ?? null;
                
                // Skip if not our Instagram account
                if ($instagramAccountId !== $tenant->instagram_account_id) {
                    Log::info("Skipping message for different IG account: {$instagramAccountId}");
                    continue;
                }
                
                // Process messaging events
                foreach ($entry['messaging'] ?? [] as $messaging) {
                    $this->processInstagramMessage($tenant, $messaging);
                }
            }
            
            return response()->json(['status' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('Instagram webhook processing error', [
                'tenant' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Process a single Instagram message
     */
    private function processInstagramMessage(Tenant $tenant, array $messaging)
    {
        $senderId = $messaging['sender']['id'] ?? null;
        $recipientId = $messaging['recipient']['id'] ?? null;
        $message = $messaging['message'] ?? null;
        
        if (!$senderId || !$message) {
            return;
        }
        
        $messageText = $message['text'] ?? '';
        $messageId = $message['mid'] ?? null;
        
        Log::info('Instagram message received', [
            'tenant' => $tenant->name,
            'sender_id' => $senderId,
            'message' => $messageText,
        ]);
        
        // Find or create conversation
        $conversation = Conversation::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'platform' => 'instagram',
                'customer_id' => $senderId,
            ],
            [
                'status' => 'active',
                'customer_name' => 'Instagram User',
                'last_message_at' => now(),
            ]
        );
        
        // Store incoming message
        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'content' => $messageText,
            'message_type' => 'text',
            'metadata' => ['message_id' => $messageId],
        ]);
        
        // Update conversation
        $conversation->update(['last_message_at' => now()]);
        
        // Process with AI and send response
        $this->processWithAI($tenant, $conversation, $messageText);
    }

    /**
     * Process message with AI (Brain service)
     */
    private function processWithAI(Tenant $tenant, Conversation $conversation, string $messageText)
    {
        // Get chatbot config
        $config = ChatbotConfig::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();
        
        if (!$config) {
            Log::warning("No chatbot config for tenant: {$tenant->name}");
            return;
        }
        
        // Get tenant services for context
        $services = $tenant->services()
            ->where('is_active', true)
            ->get()
            ->map(fn($s) => [
                'name' => $s->name,
                'description' => $s->description,
                'price' => $s->price,
                'keywords' => $s->keywords,
            ])
            ->toArray();
        
        try {
            $brainUrl = config('services.brain.url', 'http://brain:8000');
            
            $response = Http::timeout(30)->post("{$brainUrl}/api/webhook/chat", [
                'organization_id' => $tenant->id,
                'platform' => 'instagram',
                'customer_id' => $conversation->customer_id,
                'message' => $messageText,
                'conversation_history' => [],
                'thread_id' => $conversation->id,
                'system_prompt' => $config->system_prompt,
                'skills' => $config->skills ?? [],
                'behaviors' => $config->behaviors ?? [],
                'services' => $services,
                'tenant_name' => $tenant->name,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['reply'] ?? $config->fallback_message;
                
                // Send reply via Instagram API
                $this->sendInstagramReply($tenant, $conversation->customer_id, $reply);
                
                // Store bot response
                ConversationMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_type' => 'bot',
                    'content' => $reply,
                    'message_type' => 'text',
                ]);
                
                Log::info('AI response sent', [
                    'tenant' => $tenant->name,
                    'sender_id' => $conversation->customer_id,
                    'reply_length' => strlen($reply),
                ]);
            } else {
                Log::error('Brain service error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                // Send fallback message
                $this->sendInstagramReply($tenant, $conversation->customer_id, $config->fallback_message);
            }
            
        } catch (\Exception $e) {
            Log::error('AI processing error', [
                'tenant' => $tenant->name,
                'error' => $e->getMessage(),
            ]);
            
            // Send fallback on error
            $this->sendInstagramReply($tenant, $conversation->customer_id, $config->fallback_message);
        }
    }

    /**
     * Send reply via Instagram Graph API
     */
    private function sendInstagramReply(Tenant $tenant, string $recipientId, string $message)
    {
        $token = $tenant->getDecryptedInstagramToken();
        $igUserId = $tenant->instagram_account_id;
        
        if (!$token || !$igUserId) {
            Log::error('Instagram credentials missing', ['tenant' => $tenant->id]);
            return false;
        }
        
        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("https://graph.instagram.com/v18.0/{$igUserId}/messages", [
                    'recipient' => ['id' => $recipientId],
                    'message' => ['text' => $message],
                ]);
            
            if (!$response->successful()) {
                Log::error('Instagram API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Instagram send exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Log webhook for debugging
     */
    private function logWebhook(Tenant $tenant, string $platform, string $eventType, array $payload)
    {
        WebhookLog::create([
            'tenant_id' => $tenant->id,
            'platform' => $platform,
            'event_type' => $eventType,
            'payload' => $payload,
            'processed' => true,
        ]);
    }
}