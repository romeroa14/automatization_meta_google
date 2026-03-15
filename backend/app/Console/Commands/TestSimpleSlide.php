<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestSimpleSlide extends Command
{
    protected $signature = 'slides:test-simple';
    protected $description = 'Prueba una diapositiva extremadamente simple';

    public function handle()
    {
        $this->info('🧪 Probando diapositiva extremadamente simple...');
        
        $webappUrl = env('GOOGLE_WEBAPP_URL_slides');
        $this->info("🔗 URL: {$webappUrl}");
        
        try {
            // 1. Crear presentación
            $this->info('📄 Creando presentación...');
            $presentationResponse = Http::timeout(30)->post($webappUrl, [
                'action' => 'create_presentation',
                'title' => 'PRUEBA SIMPLE - ' . now()->format('H:i:s'),
                'description' => 'Prueba de diapositiva simple'
            ]);
            
            $this->info("📊 Status: {$presentationResponse->status()}");
            $this->info("📄 Response: " . $presentationResponse->body());
            
            if (!$presentationResponse->successful()) {
                $this->error('❌ Error creando presentación');
                return 1;
            }
            
            $presentationData = $presentationResponse->json();
            if (!isset($presentationData['success']) || !$presentationData['success']) {
                $this->error('❌ Error en respuesta de presentación');
                return 1;
            }
            
            $presentationId = $presentationData['data']['presentation_id'];
            $this->info("✅ Presentación creada: {$presentationId}");
            
            // 2. Crear diapositiva MUY simple
            $this->info('📄 Creando diapositiva simple...');
            $slideResponse = Http::timeout(30)->post($webappUrl, [
                'action' => 'create_slide',
                'presentation_id' => $presentationId,
                'slide_index' => 1,
                'slide_data' => [
                    'type' => 'simple',
                    'title' => 'PRUEBA SIMPLE',
                    'subtitle' => 'Esta es una prueba',
                    'content' => [
                        'texto1' => 'Hola mundo',
                        'texto2' => 'Funciona',
                        'numero' => '123',
                    ]
                ]
            ]);
            
            $this->info("📊 Status: {$slideResponse->status()}");
            $this->info("📄 Response: " . $slideResponse->body());
            
            if ($slideResponse->successful()) {
                $slideData = $slideResponse->json();
                if (isset($slideData['success']) && $slideData['success']) {
                    $this->info('✅ Diapositiva creada exitosamente!');
                    $this->info("🔗 URL: https://docs.google.com/presentation/d/{$presentationId}/edit");
                    $this->info('📋 Por favor, abre la URL y verifica si la diapositiva tiene contenido.');
                } else {
                    $this->error('❌ Error en respuesta de diapositiva');
                }
            } else {
                $this->error('❌ Error creando diapositiva');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
