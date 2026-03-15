<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetupTelegramWebhook extends Command
{
    protected $signature = 'telegram:setup-webhook {bot_token} {webhook_url}';
    protected $description = 'Configura el webhook de Telegram para el bot';

    public function handle()
    {
        $botToken = $this->argument('bot_token');
        $webhookUrl = $this->argument('webhook_url');

        $this->info('🤖 Configurando webhook de Telegram...');
        $this->info("Bot Token: {$botToken}");
        $this->info("Webhook URL: {$webhookUrl}");

        try {
            // Configurar webhook
            $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok']) {
                    $this->info('✅ Webhook configurado exitosamente');
                    $this->info("Descripción: {$data['description']}");
                    
                    // Obtener información del bot
                    $botInfo = Http::get("https://api.telegram.org/bot{$botToken}/getMe");
                    if ($botInfo->successful()) {
                        $botData = $botInfo->json();
                        if ($botData['ok']) {
                            $bot = $botData['result'];
                            $this->info("Bot: @{$bot['username']} ({$bot['first_name']})");
                        }
                    }
                } else {
                    $this->error('❌ Error al configurar webhook: ' . $data['description']);
                    return 1;
                }
            } else {
                $this->error('❌ Error HTTP: ' . $response->status());
                return 1;
            }

            // Verificar webhook
            $this->info('🔍 Verificando webhook...');
            $webhookInfo = Http::get("https://api.telegram.org/bot{$botToken}/getWebhookInfo");
            if ($webhookInfo->successful()) {
                $webhookData = $webhookInfo->json();
                if ($webhookData['ok']) {
                    $info = $webhookData['result'];
                    $this->info("URL: {$info['url']}");
                    $this->info("Pending updates: {$info['pending_update_count']}");
                    if (isset($info['last_error_message'])) {
                        $this->warn("Último error: {$info['last_error_message']}");
                    }
                }
            }

            Log::info('✅ Webhook de Telegram configurado exitosamente', [
                'bot_token' => substr($botToken, 0, 10) . '...',
                'webhook_url' => $webhookUrl,
                'timestamp' => now()
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('❌ Error configurando webhook de Telegram', [
                'error' => $e->getMessage(),
                'bot_token' => substr($botToken, 0, 10) . '...',
                'webhook_url' => $webhookUrl
            ]);
            return 1;
        }
    }
}
