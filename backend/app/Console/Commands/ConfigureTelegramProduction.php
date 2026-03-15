<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;

class ConfigureTelegramProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:configure-production {--token= : Token del bot de Telegram} {--webhook= : URL del webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura el bot de Telegram para producción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configurando Bot de Telegram para Producción...');
        
        // Obtener token del parámetro o variable de entorno
        $token = $this->option('token') ?: env('TELEGRAM_BOT_TOKEN');
        $webhookUrl = $this->option('webhook') ?: env('TELEGRAM_WEBHOOK_URL');
        
        if (!$token) {
            $this->error('❌ Token de Telegram no encontrado. Usa --token=TU_TOKEN o configura TELEGRAM_BOT_TOKEN en .env');
            return 1;
        }
        
        if (!$webhookUrl) {
            $this->error('❌ URL del webhook no encontrada. Usa --webhook=TU_URL o configura TELEGRAM_WEBHOOK_URL en .env');
            return 1;
        }
        
        try {
            $botService = new TelegramBotService();
            
            // 1. Obtener información del bot
            $this->info('📱 Obteniendo información del bot...');
            $botInfo = $botService->getBotInfo($token);
            
            if ($botInfo) {
                $this->info("✅ Bot encontrado: @{$botInfo['username']} - {$botInfo['first_name']}");
            } else {
                $this->error('❌ No se pudo obtener información del bot. Verifica el token.');
                return 1;
            }
            
            // 2. Configurar webhook
            $this->info('🔗 Configurando webhook...');
            $webhookResult = $botService->setWebhook($token, $webhookUrl);
            
            if ($webhookResult) {
                $this->info("✅ Webhook configurado: {$webhookUrl}");
            } else {
                $this->error('❌ Error al configurar webhook');
                return 1;
            }
            
            // 3. Verificar configuración
            $this->info('🔍 Verificando configuración...');
            $this->table(
                ['Configuración', 'Valor'],
                [
                    ['Bot Token', substr($token, 0, 10) . '...'],
                    ['Webhook URL', $webhookUrl],
                    ['Bot Username', '@' . $botInfo['username']],
                    ['Bot Name', $botInfo['first_name']],
                ]
            );
            
            $this->info('🎉 ¡Bot de Telegram configurado exitosamente para producción!');
            $this->info('💡 Ahora puedes probar enviando /start a tu bot en Telegram');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la configuración: ' . $e->getMessage());
            Log::error('Error configurando bot de Telegram: ' . $e->getMessage());
            return 1;
        }
    }
}
