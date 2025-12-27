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
            'message' => 'nullable|string', // Mensaje del cliente
            'response' => 'nullable|string', // Respuesta del modelo/n8n
            'intent' => 'nullable|string',
            'message_id' => 'nullable|string', // ID del mensaje de WhatsApp
            'response_id' => 'nullable|string', // ID de la respuesta enviada
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

            // 4. Create Conversation Record for the CLIENT message (if exists)
            if ($request->filled('message')) {
                $lead->conversations()->create([
                    'user_id' => $user->id,
                    'message_id' => $request->message_id,
                    'message_text' => $request->message,
                    'is_client_message' => true,
                    'is_employee' => false,
                    'platform' => 'whatsapp',
                    'timestamp' => now()->toDateTimeString(),
                    'message_length' => strlen($request->message),
                ]);
                
                Log::info("✅ Mensaje del cliente guardado", [
                    'lead_id' => $lead->id,
                    'message_id' => $request->message_id,
                ]);
            }

            // 5. Create Conversation Record for the MODEL/BOT response (if exists)
            if ($request->filled('response')) {
                $lead->conversations()->create([
                    'user_id' => $user->id,
                    'message_id' => $request->response_id,
                    'response' => $request->response, // La respuesta va en el campo 'response'
                    'message_text' => $request->response, // También en message_text para mostrar en el chat
                    'is_client_message' => false, // Es respuesta del sistema
                    'is_employee' => true, // Es del bot/empleado
                    'platform' => 'whatsapp',
                    'timestamp' => now()->toDateTimeString(),
                    'message_length' => strlen($request->response),
                    'status' => 'sent',
                ]);
                
                Log::info("✅ Respuesta del modelo guardada", [
                    'lead_id' => $lead->id,
                    'response_id' => $request->response_id,
                ]);
            }

            // 6. Trigger AI Analysis (solo si hay mensaje del cliente, no si solo es respuesta)
            if ($request->filled('message')) {
                \App\Jobs\AnalyzeLeadJob::dispatch($lead->id);
            }

            // 7. Log interaction
            Log::info("Lead procesado desde n8n", [
                'user_id' => $user->id,
                'lead_id' => $lead->id,
                'client_name' => $lead->client_name,
                'has_message' => $request->filled('message'),
                'has_response' => $request->filled('response'),
            ]);

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
