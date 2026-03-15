<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Request;

class TestWebhook extends Command
{
    protected $signature = 'telegram:test-webhook';
    protected $description = 'Prueba el webhook de Telegram localmente';

    public function handle()
    {
        $this->info("🧪 Probando webhook de Telegram localmente...");
        
        // Simular request de Telegram
        $telegramData = [
            'update_id' => 999999,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 1303627853,
                    'first_name' => 'Test',
                    'username' => 'test'
                ],
                'chat' => [
                    'id' => 1303627853,
                    'type' => 'private'
                ],
                'date' => time(),
                'text' => '/crear_campana'
            ]
        ];
        
        // Crear request simulado
        $request = Request::create('/telegram-webhook', 'POST', $telegramData);
        $request->headers->set('Content-Type', 'application/json');
        
        // Crear controlador
        $controller = new TelegramWebhookController();
        
        try {
            $this->info("📤 Enviando request simulado...");
            $response = $controller->handle($request);
            
            $this->info("✅ Respuesta recibida:");
            $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error en webhook: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
