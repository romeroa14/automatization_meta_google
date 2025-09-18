<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Http\Request;

class TelegramPolling extends Command
{
    protected $signature = 'telegram:polling {--daemon : Ejecutar en modo daemon}';
    protected $description = 'Ejecuta polling continuo de mensajes de Telegram';

    public function handle()
    {
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $lastUpdateId = cache('telegram_last_update_id', 0);
        $daemon = $this->option('daemon');
        
        $this->info("🤖 Iniciando polling de Telegram...");
        $this->info("🔄 Modo: " . ($daemon ? 'Daemon (continuo)' : 'Una vez'));
        $this->info("📡 Último update_id: {$lastUpdateId}");
        
        do {
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
                        $this->info("📨 " . count($data['result']) . " mensajes encontrados");
                        
                        foreach ($data['result'] as $update) {
                            $this->processUpdate($update);
                            $lastUpdateId = $update['update_id'];
                        }
                        
                        // Guardar el último update_id procesado
                        cache(['telegram_last_update_id' => $lastUpdateId], 3600);
                        
                        $this->info("✅ Procesados " . count($data['result']) . " mensajes");
                    } else {
                        if ($daemon) {
                            $this->info("📭 No hay mensajes pendientes, esperando...");
                            sleep(5); // Esperar 5 segundos antes del siguiente polling
                        }
                    }
                } else {
                    $this->error("❌ Error obteniendo mensajes: " . $response->body());
                    if ($daemon) {
                        sleep(10); // Esperar más tiempo en caso de error
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Excepción: " . $e->getMessage());
                if ($daemon) {
                    sleep(10);
                }
            }
            
        } while ($daemon);
        
        $this->info("🏁 Polling completado");
    }
    
    private function processUpdate($update)
    {
        try {
            if (isset($update['message'])) {
                $message = $update['message'];
                $this->info("💬 {$message['from']['first_name']}: {$message['text']}");
                
                // Simular request HTTP
                $request = new Request();
                $request->merge(['message' => $message]);
                
                // Procesar con el controlador
                $controller = new TelegramWebhookController();
                $response = $controller->handle($request);
                
                $this->info("✅ Respuesta enviada");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error procesando update {$update['update_id']}: " . $e->getMessage());
        }
    }
}