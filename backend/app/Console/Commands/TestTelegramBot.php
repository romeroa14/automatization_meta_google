<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestTelegramBot extends Command
{
    protected $signature = 'telegram:test {chat_id} {message}';
    protected $description = 'Envía un mensaje de prueba al bot de Telegram';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $message = $this->argument('message');
        $botToken = config('services.telegram.bot_token');

        if (!$botToken) {
            $this->error('❌ Bot token de Telegram no configurado');
            $this->info('Configura TELEGRAM_BOT_TOKEN en tu archivo .env');
            return 1;
        }

        $this->info("🤖 Enviando mensaje de prueba...");
        $this->info("Chat ID: {$chatId}");
        $this->info("Mensaje: {$message}");

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if ($data['ok']) {
                    $this->info('✅ Mensaje enviado exitosamente');
                    $this->info("Message ID: {$data['result']['message_id']}");
                    return 0;
                } else {
                    $this->error('❌ Error: ' . $data['description']);
                    return 1;
                }
            } else {
                $this->error('❌ Error HTTP: ' . $response->status());
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error enviando mensaje de prueba', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'message' => $message
            ]);
            return 1;
        }
    }
}
