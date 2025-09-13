<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    private TelegramBotService $telegramBotService;

    public function __construct(TelegramBotService $telegramBotService)
    {
        $this->telegramBotService = $telegramBotService;
    }

    /**
     * Maneja los webhooks de Telegram
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Log del webhook para debugging
            Log::info('Telegram webhook recibido:', $data);
            
            // Procesar el mensaje
            $this->telegramBotService->handleWebhook($data);
            
            return response()->json(['status' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('Error en webhook de Telegram: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Configura el webhook de Telegram
     */
    public function setWebhook(): JsonResponse
    {
        try {
            $success = $this->telegramBotService->setWebhook();
            
            if ($success) {
                return response()->json(['status' => 'success', 'message' => 'Webhook configurado correctamente']);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Error al configurar webhook'], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al configurar webhook: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene información del bot
     */
    public function getBotInfo(): JsonResponse
    {
        try {
            $response = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot" . config('telegram.bot_token') . "/getMe");
            $data = $response->json();
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener info del bot: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
