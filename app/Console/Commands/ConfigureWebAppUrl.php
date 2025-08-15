<?php

namespace App\Console\Commands;

use App\Models\GoogleSheet;
use Illuminate\Console\Command;

class ConfigureWebAppUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:configure-webapp {url : URL del Google Apps Script Web App}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura la URL del Google Apps Script Web App para actualizaciones automáticas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        
        $this->info('🔧 Configurando URL del Web App...');
        $this->info("📡 URL: {$url}");
        
        try {
            // Obtener el primer Google Sheet
            $googleSheet = GoogleSheet::first();
            
            if (!$googleSheet) {
                $this->error('❌ No se encontró ningún Google Sheet configurado.');
                $this->info('💡 Primero crea un Google Sheet en la interfaz web.');
                return 1;
            }
            
            // Actualizar la URL del web app
            $googleSheet->update(['webapp_url' => $url]);
            
            $this->info("✅ URL configurada exitosamente para: {$googleSheet->name}");
            $this->info("📊 Spreadsheet: {$googleSheet->spreadsheet_id}");
            $this->info("📋 Hoja: {$googleSheet->worksheet_name}");
            
            // Probar la URL
            $this->info('🧪 Probando la URL del web app...');
            $this->testWebAppUrl($url);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error configurando URL: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Prueba la URL del web app
     */
    private function testWebAppUrl($url)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['success']) && $result['success']) {
                    $this->info('✅ Web app funcionando correctamente');
                    $this->info("📝 Mensaje: {$result['message']}");
                    $this->info("⏰ Timestamp: {$result['timestamp']}");
                } else {
                    $this->warn('⚠️ Web app respondió pero con error');
                    $this->warn("📝 Mensaje: {$result['message']}");
                }
            } else {
                $this->error("❌ Error HTTP: {$response->status()}");
                $this->error("📝 Respuesta: {$response->body()}");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error probando web app: ' . $e->getMessage());
        }
    }
}
