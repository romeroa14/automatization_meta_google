<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestIntermediateSlide extends Command
{
    protected $signature = 'slides:test-intermediate';
    protected $description = 'Prueba una diapositiva con datos intermedios';

    public function handle()
    {
        $this->info('🧪 Probando diapositiva con datos intermedios...');
        
        $webappUrl = env('GOOGLE_WEBAPP_URL_slides');
        
        try {
            // 1. Crear presentación
            $this->info('📄 Creando presentación...');
            $presentationResponse = Http::timeout(30)->post($webappUrl, [
                'action' => 'create_presentation',
                'title' => 'PRUEBA INTERMEDIA - ' . now()->format('H:i:s'),
                'description' => 'Prueba con datos similares a los reales'
            ]);
            
            if (!$presentationResponse->successful()) {
                $this->error('❌ Error creando presentación');
                return 1;
            }
            
            $presentationData = $presentationResponse->json();
            $presentationId = $presentationData['data']['presentation_id'];
            $this->info("✅ Presentación creada: {$presentationId}");
            
            // 2. Crear diapositiva con datos similares a los reales
            $this->info('📄 Creando diapositiva con datos reales...');
            $slideResponse = Http::timeout(30)->post($webappUrl, [
                'action' => 'create_slide',
                'presentation_id' => $presentationId,
                'slide_index' => 1,
                'slide_data' => [
                    'type' => 'campaign',
                    'title' => 'Campaña de Prueba',
                    'subtitle' => 'Campaña ID: 123456789',
                    'content' => [
                        'reach' => '1,234',
                        'impressions' => '5,678',
                        'clicks' => '123',
                        'ctr' => '2.16%',
                        'cpm' => '$1.50',
                        'cpc' => '$0.25',
                        'frequency' => '1.45',
                        'total_interactions' => '456',
                        'interaction_rate' => '8.03%',
                        'video_views_p100' => '0',
                        'video_completion_rate' => '0.00%'
                    ],
                    'layout' => 'image'
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
