<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupTelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup {--token= : Token del bot de Telegram} {--webhook= : URL del webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura el bot de Telegram para crear campañas de Meta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configurando Bot de Telegram para Campañas de Meta...');
        
        // Obtener token
        $token = $this->option('token') ?: $this->ask('Ingresa el token del bot de Telegram');
        
        if (!$token) {
            $this->error('❌ Token requerido. Obtén tu token de @BotFather en Telegram.');
            return 1;
        }
        
        // Verificar token
        $this->info('🔍 Verificando token...');
        $response = Http::get("https://api.telegram.org/bot{$token}/getMe");
        
        if (!$response->successful()) {
            $this->error('❌ Token inválido. Verifica que el token sea correcto.');
            return 1;
        }
        
        $botInfo = $response->json();
        $this->info("✅ Bot verificado: @{$botInfo['result']['username']} ({$botInfo['result']['first_name']})");
        
        // Obtener URL del webhook
        $webhookUrl = $this->option('webhook') ?: $this->ask('Ingresa la URL del webhook', config('app.url') . '/api/telegram/webhook');
        
        // Configurar webhook
        $this->info('🔗 Configurando webhook...');
        $webhookResponse = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $webhookUrl,
        ]);
        
        if ($webhookResponse->successful()) {
            $this->info('✅ Webhook configurado correctamente');
        } else {
            $this->error('❌ Error al configurar webhook: ' . $webhookResponse->body());
            return 1;
        }
        
        // Actualizar archivo .env
        $this->info('📝 Actualizando configuración...');
        $this->updateEnvFile($token, $webhookUrl);
        
        // Mostrar información del bot
        $this->displayBotInfo($botInfo, $webhookUrl);
        
        $this->info('🎉 ¡Bot configurado exitosamente!');
        $this->info('💡 Usa /start en Telegram para comenzar a crear campañas.');
        
        return 0;
    }
    
    private function updateEnvFile(string $token, string $webhookUrl): void
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        // Actualizar o agregar TELEGRAM_BOT_TOKEN
        if (strpos($envContent, 'TELEGRAM_BOT_TOKEN=') !== false) {
            $envContent = preg_replace('/TELEGRAM_BOT_TOKEN=.*/', "TELEGRAM_BOT_TOKEN={$token}", $envContent);
        } else {
            $envContent .= "\nTELEGRAM_BOT_TOKEN={$token}";
        }
        
        // Actualizar o agregar TELEGRAM_WEBHOOK_URL
        if (strpos($envContent, 'TELEGRAM_WEBHOOK_URL=') !== false) {
            $envContent = preg_replace('/TELEGRAM_WEBHOOK_URL=.*/', "TELEGRAM_WEBHOOK_URL={$webhookUrl}", $envContent);
        } else {
            $envContent .= "\nTELEGRAM_WEBHOOK_URL={$webhookUrl}";
        }
        
        file_put_contents($envFile, $envContent);
    }
    
    private function displayBotInfo(array $botInfo, string $webhookUrl): void
    {
        $this->info('');
        $this->info('📊 INFORMACIÓN DEL BOT:');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("🤖 Nombre: {$botInfo['result']['first_name']}");
        $this->info("👤 Username: @{$botInfo['result']['username']}");
        $this->info("🆔 ID: {$botInfo['result']['id']}");
        $this->info("🔗 Webhook: {$webhookUrl}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('');
        $this->info('🎯 FUNCIONALIDADES:');
        $this->info('• Crear campañas de Facebook e Instagram');
        $this->info('• Configurar presupuestos y fechas');
        $this->info('• Subir imágenes y videos');
        $this->info('• Targeting automático');
        $this->info('• Integración con Meta API');
        $this->info('');
        $this->info('📱 COMANDOS DISPONIBLES:');
        $this->info('• /start - Iniciar creación de campaña');
        $this->info('• /help - Mostrar ayuda');
        $this->info('');
    }
}
