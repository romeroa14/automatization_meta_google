<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Lead;
use App\Services\GeminiAnalysisService;
use Illuminate\Support\Facades\Log;

class AnalyzeLeadJob implements ShouldQueue
{
    use Queueable;

    protected $leadId;

    /**
     * Create a new job instance.
     */
    public function __construct($leadId)
    {
        $this->leadId = $leadId;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiAnalysisService $aiService): void
    {
        $lead = Lead::find($this->leadId);
        
        if (!$lead) {
            Log::warning("AnalyzeLeadJob: Lead {$this->leadId} not found.");
            return;
        }

        Log::info("🤖 Starting AI Analysis for Lead: {$lead->client_name} ({$lead->phone_number})");

        // 1. Fetch recent conversation history (last 10 messages)
        $messages = $lead->conversations()
                         ->orderBy('created_at', 'desc')
                         ->take(10)
                         ->get()
                         ->map(function ($msg) {
                             return [
                                 'role' => $msg->is_client_message ? 'client' : 'agent',
                                 'content' => $msg->message_text,
                                 'timestamp' => $msg->created_at->toIso8601String(),
                             ];
                         })
                         ->reverse()
                         ->values()
                         ->toArray();

        if (empty($messages)) {
            Log::info("AnalyzeLeadJob: No messages to analyze.");
            return;
        }

        // 2. Call AI Service
        $analysis = $aiService->analyzeConversation($messages);

        // 3. Update Lead
        $lead->update([
            'intent' => $analysis['intent'] ?? $lead->intent,
            'confidence_score' => isset($analysis['lead_score']) ? ($analysis['lead_score'] / 100) : $lead->confidence_score,
            'stage' => $this->mapIntentToStage($analysis['intent'] ?? ''),
        ]);

        // 4. Update latest conversation (or create a system insight note)
        // For simplicity, we assume we just want to log the insight on the lead or last message.
        // Let's attach the insight to the *last* client message if possible, or just Log it.
        // Ideally, we store this in a separate 'ai_insights' table or column, but the plan said update 'conversations'.
        
        $lastMsg = $lead->conversations()->orderBy('created_at', 'desc')->first();
        if ($lastMsg) {
            $lastMsg->update([
                'lead_intent' => $analysis['intent'] ?? null,
                'message_sentiment' => $analysis['sentiment'] ?? null,
                'conversation_summary' => $analysis['summary'] ?? null,
                'response' => $analysis['suggested_reply'] ?? null, // Storing suggestion in 'response' field
            ]);
        }

        Log::info("✅ AI Analysis Complete. Intent: {$lead->intent}, Score: {$lead->confidence_score}");
    }

    protected function mapIntentToStage(string $intent): string
    {
        // Simple mapping logic, customizable
        return match (strtolower($intent)) {
            'compra', 'payment', 'interesado' => 'interesado',
            'reclamo' => 'problema',
            'spam' => 'descalificado',
            default => 'contactado',
        };
    }
}
