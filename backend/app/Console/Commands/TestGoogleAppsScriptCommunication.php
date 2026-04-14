<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestGoogleAppsScriptCommunication extends Command
{
    protected $signature = 'test:google-apps-script {reportId}';
    protected $description = 'Prueba la comunicación con Google Apps Script';

    public function handle()
    {
        $reportId = $this->argument('reportId');
        $webAppUrl = env('GOOGLE_WEBAPP_URL_slides');
        
        $this->info("🔍 Probando comunicación con Google Apps Script");
        $this->info("URL: {$webAppUrl}");
        
        // 1. Probar GET request
        $this->info("\n1️⃣ Probando GET request...");
        try {
            $getResponse = Http::timeout(30)->get($webAppUrl);
            $this->info("Status: " . $getResponse->status());
            $this->info("Response: " . $getResponse->body());
        } catch (\Exception $e) {
            $this->error("Error en GET: " . $e->getMessage());
        }
        
        // 2. Probar creación de presentación
        $this->info("\n2️⃣ Probando creación de presentación...");
        try {
            $createResponse = Http::timeout(30)->post($webAppUrl, [
                'action' => 'create_presentation',
                'title' => 'Test Presentation'
            ]);
            
            $this->info("Status: " . $createResponse->status());
            $this->info("Response: " . $createResponse->body());
            
            if ($createResponse->successful()) {
                $data = $createResponse->json();
                $presentationId = $data['data']['presentation_id'] ?? null;
                
                if ($presentationId) {
                    $this->info("✅ Presentación creada: {$presentationId}");
                    
                    // 3. Probar creación de slide individual
                    $this->info("\n3️⃣ Probando creación de slide individual...");
                    $slideResponse = Http::timeout(30)->post($webAppUrl, [
                        'action' => 'create_slide',
                        'presentation_id' => $presentationId,
                        'slide_data' => [
                            'type' => 'title',
                            'title' => 'Test Slide',
                            'subtitle' => 'Test Subtitle'
                        ]
                    ]);
                    
                    $this->info("Status: " . $slideResponse->status());
                    $this->info("Response: " . $slideResponse->body());
                    
                    // 4. Probar creación de múltiples slides
                    $this->info("\n4️⃣ Probando creación de múltiples slides...");
                    $multipleResponse = Http::timeout(60)->post($webAppUrl, [
                        'action' => 'create_multiple_slides',
                        'presentation_id' => $presentationId,
                        'slides' => [
                            [
                                'type' => 'title',
                                'title' => 'Test Title 1',
                                'subtitle' => 'Test Subtitle 1'
                            ],
                            [
                                'type' => 'content',
                                'title' => 'Test Content',
                                'content' => [
                                    'Test Key' => 'Test Value'
                                ]
                            ]
                        ]
                    ]);
                    
                    $this->info("Status: " . $multipleResponse->status());
                    $this->info("Response: " . $multipleResponse->body());
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error en POST: " . $e->getMessage());
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
