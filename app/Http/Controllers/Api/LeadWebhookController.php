<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class LeadWebhookController extends Controller
{
    /**
     * Handle incoming webhook from n8n.
     */
    public function handle(Request $request)
    {
        // 1. Authenticated User (via Sanctum Token)
        $user = $request->user();

        // 2. Validate Payload
        $request->validate([
            'client_phone' => 'required|string',
            'client_name' => 'nullable|string',
            'message' => 'nullable|string',
            'intent' => 'nullable|string',
            //Optional: If n8n sends business_phone, we can verify it matches user settings
        ]);

        try {
            // 3. Create or Update Lead
            $lead = Lead::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'phone_number' => $request->client_phone,
                ],
                [
                    'client_name' => $request->client_name ?? 'Desconocido',
                    'intent' => $request->intent ?? 'consulta',
                    'lead_level' => 'cold', // Default
                    'stage' => 'nuevo',
                    'confidence_score' => 0.0,
                    'updated_at' => now(),
                ]
            );

            // 4. Create Conversation Record for the message
            $lead->conversations()->create([
                'message_text' => $request->message,
                'is_client_message' => true,
                'platform' => 'whatsapp',
                'timestamp' => now(),
            ]);

            // 5. Trigger AI Analysis
            \App\Jobs\AnalyzeLeadJob::dispatch($lead->id);

            // 6. Log interaction
            Log::info("Lead Received & AI Job Dispatched for User {$user->id}: {$lead->client_name}");

            return response()->json([
                'success' => true,
                'lead_id' => $lead->id,
                'message' => 'Lead procesado correctamente',
            ]);

        } catch (\Exception $e) {
            Log::error("Error processing lead webhook: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error inteno al procesar el lead'
            ], 500);
        }
    }
}
