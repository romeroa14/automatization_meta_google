<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Request;

class ProcessTelegramMessages extends Command
{
    protected $signature = 'telegram:process-messages';
    protected $description = 'Procesa mensajes pendientes de Telegram usando polling';

    public function handle()
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $lastUpdateId = cache('telegram_last_update_id', 0);
        
        $this->info("🔄 Procesando mensajes de Telegram...");
        $this->info("📡 Último update_id procesado: {$lastUpdateId}");
        
        try {
            // Obtener mensajes pendientes
            $response = Http::get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                'offset' => $lastUpdateId + 1,
                'limit' => 10,
                'timeout' => 30
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['result']) && !empty($data['result'])) {
                    $this->info("📨 Mensajes encontrados: " . count($data['result']));
                    
                    foreach ($data['result'] as $update) {
                        $this->processUpdate($update);
                        $lastUpdateId = $update['update_id'];
                    }
                    
                    // Guardar el último update_id procesado
                    cache(['telegram_last_update_id' => $lastUpdateId], 3600);
                    
                    $this->info("✅ Procesados " . count($data['result']) . " mensajes");
                } else {
                    $this->info("📭 No hay mensajes pendientes");
                }
            } else {
                $this->error("❌ Error obteniendo mensajes: " . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción: " . $e->getMessage());
        }
    }
    
    private function processUpdate($update)
    {
        try {
            if (isset($update['message'])) {
                $message = $update['message'];
                $this->info("💬 Procesando mensaje: {$message['text']} de {$message['from']['first_name']}");
                
                // Simular request HTTP
                $request = new Request();
                $request->merge(['message' => $message]);
                
                // Procesar con el controlador
                $controller = new TelegramWebhookController();
                $response = $controller->handle($request);
                
                $this->info("✅ Mensaje procesado correctamente");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error procesando update {$update['update_id']}: " . $e->getMessage());
        }
    }
}