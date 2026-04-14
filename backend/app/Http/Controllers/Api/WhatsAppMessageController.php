<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageController extends Controller
{
    /**
     * Enviar mensaje desde la app a un lead
     * Esto deshabilita el bot automáticamente (intervención humana)
     */
    public function sendMessage(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'message' => 'required|string|max:4096',
        ]);

        try {
            $lead = Lead::findOrFail($request->lead_id);
            
            // Verificar si el usuario tiene acceso a la organización del lead
            $organization = \App\Models\Organization::find($lead->organization_id);
            if (!$organization || !$user->organizations()->where('organizations.id', $organization->id)->exists()) {
                return response()->json(['success' => false, 'error' => 'No tienes permiso para responder a este lead'], 403);
            }

            // Obtener el número de WhatsApp para enviar
            $whatsappNumber = null;
            if ($lead->whatsapp_phone_number_id) {
                $whatsappNumber = \App\Models\WhatsAppPhoneNumber::find($lead->whatsapp_phone_number_id);
            }
            
            if (!$whatsappNumber) {
                $whatsappNumber = $organization->whatsappPhoneNumbers()->where('is_default', true)->first();
            }

            if (!$whatsappNumber) {
                return response()->json(['success' => false, 'error' => 'No hay número de WhatsApp configurado para esta organización'], 400);
            }

            // Enviar mensaje a WhatsApp usando la API real
            $phoneNumberId = $whatsappNumber->phone_number_id;
            $accessToken = $whatsappNumber->getAccessTokenAttribute($whatsappNumber->getRawOriginal('access_token'));
            
            $to = preg_replace('/[^0-9]/', '', $lead->phone_number);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $request->message,
                ],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $wamid = $responseData['messages'][0]['id'] ?? null;

                // Deshabilitar bot y registrar intervención humana
                $lead->update([
                    'bot_disabled' => true,
                    'last_human_intervention_at' => now(),
                ]);

                // Guardar conversación como mensaje del empleado
                $conversation = $lead->conversations()->create([
                    'user_id' => $user->id,
                    'organization_id' => $organization->id, // Agregar para multi-tenant
                    'whatsapp_phone_number_id' => $whatsappNumber->id, // Asociar al número
                    'message_id' => $wamid,
                    'message_text' => $request->message,
                    'is_client_message' => false,
                    'is_employee' => true,
                    'platform' => 'whatsapp',
                    'timestamp' => now()->toDateTimeString(),
                    'message_length' => strlen($request->message),
                    'status' => 'sent',
                ]);

                Log::info('✅ Mensaje humano enviado desde la app (WhatsApp)', [
                    'lead_id' => $lead->id,
                    'conversation_id' => $conversation->id,
                    'wamid' => $wamid,
                    'bot_disabled' => true,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Mensaje enviado correctamente',
                    'conversation_id' => $conversation->id,
                    'wamid' => $wamid,
                    'bot_disabled' => true,
                ]);
            } else {
                Log::error('❌ Error enviando mensaje a WhatsApp desde app', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'lead_id' => $lead->id,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Error al enviar mensaje a WhatsApp',
                    'details' => $response->json(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error en sendMessage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno al enviar mensaje',
            ], 500);
        }
    }

    /**
     * Habilitar/Deshabilitar bot para un lead
     */
    public function toggleBot(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'bot_disabled' => 'required|boolean',
        ]);

        try {
            $lead = Lead::where('id', $request->lead_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $lead->update(['bot_disabled' => $request->bot_disabled]);

            Log::info('Bot toggled', [
                'lead_id' => $lead->id,
                'bot_disabled' => $lead->bot_disabled,
            ]);

            return response()->json([
                'success' => true,
                'message' => $lead->bot_disabled ? 'Bot deshabilitado' : 'Bot habilitado',
                'bot_disabled' => $lead->bot_disabled,
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling bot', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al cambiar estado del bot',
            ], 500);
        }
    }
}

