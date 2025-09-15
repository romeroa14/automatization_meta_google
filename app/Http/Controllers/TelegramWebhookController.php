<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\FacebookAccount;
use App\Models\AdvertisingPlan;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            Log::info('📱 Webhook de Telegram recibido', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
                'data' => $request->all()
            ]);

            $update = $request->all();
            
            if (isset($update['message'])) {
                return $this->handleMessage($update['message']);
            }
            
            if (isset($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query']);
            }

            Log::warning('⚠️ Tipo de update no reconocido', ['update' => $update]);
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('❌ Error en webhook de Telegram', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $userId = $message['from']['id'] ?? null;
        $username = $message['from']['username'] ?? 'Usuario';

        Log::info('💬 Mensaje recibido', [
            'chat_id' => $chatId,
            'user_id' => $userId,
            'username' => $username,
            'text' => $text
        ]);

        // Comandos disponibles
        $commands = [
            '/start' => 'startCommand',
            '/help' => 'helpCommand',
            '/crear_campana' => 'createCampaignCommand',
            '/mis_cuentas' => 'myAccountsCommand',
            '/planes' => 'plansCommand',
            '/estado' => 'statusCommand'
        ];

        $command = explode(' ', $text)[0];
        
        if (isset($commands[$command])) {
            return $this->{$commands[$command]}($chatId, $message);
        }

        // Si no es un comando, mostrar ayuda
        return $this->sendMessage($chatId, $this->getHelpMessage());
    }

    private function startCommand($chatId, $message)
    {
        $welcomeMessage = "🤖 *Bienvenido al Bot de AdMétricas*\n\n";
        $welcomeMessage .= "Soy tu asistente para crear campañas publicitarias de Meta de forma rápida y eficiente.\n\n";
        $welcomeMessage .= "📋 *Comandos disponibles:*\n";
        $welcomeMessage .= "/crear_campana - Crear una nueva campaña\n";
        $welcomeMessage .= "/mis_cuentas - Ver cuentas de Facebook disponibles\n";
        $welcomeMessage .= "/planes - Ver planes publicitarios disponibles\n";
        $welcomeMessage .= "/estado - Ver estado del sistema\n";
        $welcomeMessage .= "/help - Mostrar esta ayuda\n\n";
        $welcomeMessage .= "🚀 ¡Empecemos a crear campañas!";

        return $this->sendMessage($chatId, $welcomeMessage);
    }

    private function helpCommand($chatId, $message)
    {
        return $this->sendMessage($chatId, $this->getHelpMessage());
    }

    private function createCampaignCommand($chatId, $message)
    {
        $message = "🎯 *Crear Nueva Campaña*\n\n";
        $message .= "Para crear una campaña, necesito la siguiente información:\n\n";
        $message .= "1️⃣ *Nombre de la campaña*\n";
        $message .= "2️⃣ *Objetivo* (TRÁFICO, CONVERSIONES, ALCANCE, etc.)\n";
        $message .= "3️⃣ *Presupuesto diario* (en USD)\n";
        $message .= "4️⃣ *Duración* (días)\n";
        $message .= "5️⃣ *Cuenta de Facebook*\n\n";
        $message .= "📝 *Ejemplo:*\n";
        $message .= "Nombre: Campaña Test\n";
        $message .= "Objetivo: TRÁFICO\n";
        $message .= "Presupuesto: 10\n";
        $message .= "Duración: 7\n";
        $message .= "Cuenta: Mi Cuenta FB\n\n";
        $message .= "💡 *Tip:* Puedes enviar toda la información en un solo mensaje o paso a paso.";

        return $this->sendMessage($chatId, $message);
    }

    private function myAccountsCommand($chatId, $message)
    {
        try {
            $accounts = FacebookAccount::where('is_active', true)->get();
            
            if ($accounts->isEmpty()) {
                return $this->sendMessage($chatId, "❌ No hay cuentas de Facebook activas disponibles.");
            }

            $message = "📱 *Cuentas de Facebook Disponibles:*\n\n";
            foreach ($accounts as $account) {
                $message .= "🔹 *{$account->name}*\n";
                $message .= "   ID: `{$account->account_id}`\n";
                $message .= "   Estado: " . ($account->is_active ? "✅ Activa" : "❌ Inactiva") . "\n\n";
            }

            return $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Error obteniendo cuentas de Facebook', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "❌ Error al obtener las cuentas de Facebook.");
        }
    }

    private function plansCommand($chatId, $message)
    {
        try {
            $plans = AdvertisingPlan::where('is_active', true)->get();
            
            if ($plans->isEmpty()) {
                return $this->sendMessage($chatId, "❌ No hay planes publicitarios disponibles.");
            }

            $message = "📊 *Planes Publicitarios Disponibles:*\n\n";
            foreach ($plans as $plan) {
                $message .= "🔹 *{$plan->name}*\n";
                $message .= "   Presupuesto diario: \${$plan->daily_budget}\n";
                $message .= "   Duración: {$plan->duration_days} días\n";
                $message .= "   Precio: \${$plan->price}\n\n";
            }

            return $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Error obteniendo planes publicitarios', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "❌ Error al obtener los planes publicitarios.");
        }
    }

    private function statusCommand($chatId, $message)
    {
        try {
            $userCount = User::count();
            $activeAccounts = FacebookAccount::where('is_active', true)->count();
            $activePlans = AdvertisingPlan::where('is_active', true)->count();

            $statusMessage = "📊 *Estado del Sistema*\n\n";
            $statusMessage .= "👥 Usuarios: {$userCount}\n";
            $statusMessage .= "📱 Cuentas FB activas: {$activeAccounts}\n";
            $statusMessage .= "📋 Planes activos: {$activePlans}\n";
            $statusMessage .= "🕐 Última actualización: " . now()->format('d/m/Y H:i:s') . "\n\n";
            $statusMessage .= "✅ Sistema funcionando correctamente";

            return $this->sendMessage($chatId, $statusMessage);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estado del sistema', ['error' => $e->getMessage()]);
            return $this->sendMessage($chatId, "❌ Error al obtener el estado del sistema.");
        }
    }

    private function handleCallbackQuery(array $callbackQuery)
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        $queryId = $callbackQuery['id'];

        Log::info('🔘 Callback query recibido', [
            'chat_id' => $chatId,
            'data' => $data,
            'query_id' => $queryId
        ]);

        // Aquí manejaremos las respuestas a botones inline
        // Por ahora, solo confirmamos la recepción
        $this->answerCallbackQuery($queryId, "Comando procesado: {$data}");
        
        return response()->json(['ok' => true]);
    }

    private function sendMessage($chatId, $text, $parseMode = 'Markdown')
    {
        $botToken = config('services.telegram.bot_token');
        
        if (!$botToken) {
            Log::error('❌ Bot token de Telegram no configurado');
            return response()->json(['ok' => false, 'error' => 'Bot token not configured']);
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok']) {
                    Log::info('✅ Mensaje enviado exitosamente', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id']
                    ]);
                    return response()->json(['ok' => true]);
                } else {
                    Log::error('❌ Error enviando mensaje', ['error' => $data['description']]);
                    return response()->json(['ok' => false, 'error' => $data['description']]);
                }
            } else {
                Log::error('❌ Error HTTP enviando mensaje', ['status' => $response->status()]);
                return response()->json(['ok' => false, 'error' => 'HTTP error']);
            }

        } catch (\Exception $e) {
            Log::error('❌ Excepción enviando mensaje', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private function answerCallbackQuery($queryId, $text = null)
    {
        $botToken = config('services.telegram.bot_token');
        
        if (!$botToken) {
            return;
        }

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                'callback_query_id' => $queryId,
                'text' => $text,
                'show_alert' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Error respondiendo callback query', ['error' => $e->getMessage()]);
        }
    }

    private function getHelpMessage()
    {
        return "🤖 *Bot de AdMétricas - Ayuda*\n\n" .
               "📋 *Comandos disponibles:*\n" .
               "/start - Iniciar el bot\n" .
               "/crear_campana - Crear nueva campaña\n" .
               "/mis_cuentas - Ver cuentas disponibles\n" .
               "/planes - Ver planes publicitarios\n" .
               "/estado - Estado del sistema\n" .
               "/help - Mostrar esta ayuda\n\n" .
               "💡 *Tip:* Usa /crear_campana para comenzar a crear campañas publicitarias.";
    }
}