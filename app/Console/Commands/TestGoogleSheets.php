<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGoogleSheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:test-sheets {spreadsheet-id : ID del Google Sheet}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la conectividad con Google Sheets y diagnostica problemas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $spreadsheetId = $this->argument('spreadsheet-id');
        
        $this->info("🔍 Probando conectividad con Google Sheet: {$spreadsheetId}");
        $this->newLine();
        
        // Paso 1: Probar acceso público
        $this->info('1️⃣ Probando acceso público...');
        $this->testPublicAccess($spreadsheetId);
        
        // Paso 2: Probar Web App
        $this->info('2️⃣ Probando Web App...');
        $this->testWebApp($spreadsheetId);
        
        // Paso 3: Probar método alternativo
        $this->info('3️⃣ Probando método alternativo...');
        $this->testAlternativeMethod($spreadsheetId);
        
        $this->newLine();
        $this->info('✅ Diagnóstico completado');
    }
    
    private function testPublicAccess($spreadsheetId): void
    {
        try {
            $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:json&tq=SELECT%20*%20LIMIT%201";
            
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                $this->info('   ✅ Acceso público: FUNCIONA');
                
                // Intentar extraer información de hojas
                $content = $response->body();
                
                // Buscar información de hojas en el contenido
                if (preg_match('/"sheets":\s*\[(.*?)\]/', $content, $matches)) {
                    $this->info('   📋 Información de hojas encontrada en la respuesta');
                    
                    // Extraer nombres de hojas
                    if (preg_match_all('/"name":\s*"([^"]+)"/', $matches[1], $sheetMatches)) {
                        $sheets = $sheetMatches[1];
                        $this->info('   📄 Hojas encontradas: ' . implode(', ', $sheets));
                    } else {
                        $this->warn('   ⚠️  No se pudieron extraer nombres de hojas');
                    }
                } else {
                    $this->warn('   ⚠️  No se encontró información de hojas en la respuesta');
                }
                
            } else {
                $this->error('   ❌ Acceso público: FALLA');
                $this->error('   Código de respuesta: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error en acceso público: ' . $e->getMessage());
        }
    }
    
    private function testWebApp($spreadsheetId): void
    {
        try {
            $webappUrl = config('services.google.webapp_url') ?? env('GOOGLE_WEBAPP_URL');
            
            if (empty($webappUrl)) {
                $this->error('   ❌ URL del Web App no configurada');
                return;
            }
            
            $this->info('   🔗 URL del Web App: ' . $webappUrl);
            
            $response = Http::timeout(30)
                ->withOptions(['allow_redirects' => true])
                ->get($webappUrl, [
                    'action' => 'list_sheets',
                    'spreadsheet_id' => $spreadsheetId
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['success']) && $result['success']) {
                    $this->info('   ✅ Web App: FUNCIONA');
                    if (isset($result['sheets'])) {
                        $this->info('   📄 Hojas encontradas: ' . implode(', ', $result['sheets']));
                    }
                } else {
                    $this->warn('   ⚠️  Web App: Respuesta con error');
                    $this->warn('   Mensaje: ' . ($result['message'] ?? 'Sin mensaje'));
                }
            } else {
                $this->error('   ❌ Web App: FALLA');
                $this->error('   Código de respuesta: ' . $response->status());
                $this->error('   Respuesta: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error en Web App: ' . $e->getMessage());
        }
    }
    
    private function testAlternativeMethod($spreadsheetId): void
    {
        try {
            // Intentar obtener información usando la API de metadatos
            $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/pub?output=json";
            
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                $this->info('   ✅ Método alternativo: FUNCIONA');
                
                $content = $response->body();
                
                // Buscar información de hojas
                if (preg_match_all('/"name":\s*"([^"]+)"/', $content, $matches)) {
                    $sheets = array_unique($matches[1]);
                    $this->info('   📄 Hojas encontradas: ' . implode(', ', $sheets));
                } else {
                    $this->warn('   ⚠️  No se pudieron extraer nombres de hojas');
                }
                
            } else {
                $this->error('   ❌ Método alternativo: FALLA');
                $this->error('   Código de respuesta: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error en método alternativo: ' . $e->getMessage());
        }
    }
}
