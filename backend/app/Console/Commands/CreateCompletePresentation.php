<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\FacebookDataForSlidesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CreateCompletePresentation extends Command
{
    protected $signature = 'slides:create-complete {report_id?}';
    protected $description = 'Crea presentación completa con estructura: membrete, objetivos, marcas, anuncios y métricas';

    public function handle()
    {
        $this->info('🎯 Creando presentación completa con estructura específica...');
        
        $reportId = $this->argument('report_id') ?? 2;
        $report = Report::find($reportId);
        
        if (!$report) {
            $this->error("❌ No se encontró el reporte con ID: {$reportId}");
            return 1;
        }
        
        $this->info("📊 Usando reporte: {$report->name}");
        
        try {
            // Obtener datos reales de Facebook
            $service = new FacebookDataForSlidesService();
            $facebookData = $service->getFacebookDataForReport($report);
            
            $this->info('✅ Datos obtenidos de Facebook');
            
            // Crear presentación
            $webappUrl = env('GOOGLE_WEBAPP_URL_slides');
            $this->info('📄 Creando presentación...');
            
            $presentationResponse = Http::timeout(30)->post($webappUrl, [
                'action' => 'create_presentation',
                'title' => $facebookData['title'],
                'description' => $facebookData['subtitle']
            ]);
            
            if (!$presentationResponse->successful()) {
                $this->error('❌ Error creando presentación');
                return 1;
            }
            
            $presentationData = $presentationResponse->json();
            $presentationId = $presentationData['data']['presentation_id'];
            $this->info("✅ Presentación creada: {$presentationId}");
            
            $slideIndex = 1;
            
            // 1. SLIDE: MEMBRETE INFORMATIVO
            $this->info("📄 Creando slide {$slideIndex}: Membrete informativo");
            $this->createMembreteSlide($webappUrl, $presentationId, $slideIndex, $facebookData);
            $slideIndex++;
            
            // 2. SLIDE: OBJETIVOS DE LAS CAMPAÑAS
            $this->info("📄 Creando slide {$slideIndex}: Objetivos de las campañas");
            $this->createObjetivosSlide($webappUrl, $presentationId, $slideIndex, $facebookData);
            $slideIndex++;
            
            // 3. PROCESAR CADA MARCA
            foreach ($facebookData['brands'] as $brand) {
                $this->info("🏷️ Procesando marca: {$brand['name']}");
                
                // SLIDE: TÍTULO DE LA MARCA
                $this->info("📄 Creando slide {$slideIndex}: Título de {$brand['name']}");
                $this->createBrandTitleSlide($webappUrl, $presentationId, $slideIndex, $brand);
                $slideIndex++;
                
                // SLIDES: ANUNCIOS DE LA MARCA
                foreach ($brand['ads'] as $ad) {
                    $this->info("📄 Creando slide {$slideIndex}: Anuncio de {$ad['name']}");
                    $this->createAdSlide($webappUrl, $presentationId, $slideIndex, $ad);
                    $slideIndex++;
                }
            }
            
            // SLIDE FINAL: MÉTRICAS POR MARCA
            $this->info("📄 Creando slide {$slideIndex}: Métricas por marca");
            $this->createMetricsSlide($webappUrl, $presentationId, $slideIndex, $facebookData);
            
            $this->info('🎉 ¡Presentación completa creada exitosamente!');
            $this->info("🔗 URL: https://docs.google.com/presentation/d/{$presentationId}/edit");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
    
    private function createMembreteSlide($webappUrl, $presentationId, $slideIndex, $facebookData)
    {
        $response = Http::timeout(60)->post($webappUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $slideIndex,
            'slide_data' => [
                'title' => 'REPORTE MULTI-CUENTA',
                'subtitle' => $facebookData['subtitle'],
                'type' => 'membrete'
            ]
        ]);
        
        if ($response->successful()) {
            $this->info("✅ Slide {$slideIndex} creado");
        } else {
            $this->error("❌ Error en slide {$slideIndex}");
        }
    }
    
    private function createObjetivosSlide($webappUrl, $presentationId, $slideIndex, $facebookData)
    {
        $response = Http::timeout(60)->post($webappUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $slideIndex,
            'slide_data' => [
                'title' => 'OBJETIVOS DE LAS CAMPAÑAS',
                'subtitle' => 'Análisis de rendimiento y métricas clave',
                'type' => 'objetivos'
            ]
        ]);
        
        if ($response->successful()) {
            $this->info("✅ Slide {$slideIndex} creado");
        } else {
            $this->error("❌ Error en slide {$slideIndex}");
        }
    }
    
    private function createBrandTitleSlide($webappUrl, $presentationId, $slideIndex, $brand)
    {
        $response = Http::timeout(60)->post($webappUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $slideIndex,
            'slide_data' => [
                'title' => $brand['name'],
                'subtitle' => 'Campañas y anuncios',
                'type' => 'brand_title'
            ]
        ]);
        
        if ($response->successful()) {
            $this->info("✅ Slide {$slideIndex} creado");
        } else {
            $this->error("❌ Error en slide {$slideIndex}");
        }
    }
    
    private function createAdSlide($webappUrl, $presentationId, $slideIndex, $ad)
    {
        // Preparar métricas para los 14 shapes
        $statistics = $ad['statistics'] ?? [];
        $metrics = [
            'alcance' => number_format($statistics['reach'] ?? 0),
            'impresiones' => number_format($statistics['impressions'] ?? 0),
            'frecuencia' => number_format($statistics['frequency'] ?? 0, 2),
            'clicks' => number_format($statistics['clicks'] ?? 0),
            'ctr' => number_format($statistics['ctr'] ?? 0, 2) . '%',
            'costo_por_resultado' => '$' . number_format($statistics['cost_per_result'] ?? 0, 4),
            'importe_gastado' => '$' . number_format($statistics['spend'] ?? 0, 2),
            'resultados' => number_format($statistics['results'] ?? 0),
            'cpm' => '$' . number_format($statistics['cpm'] ?? 0, 2),
            'cpc' => '$' . number_format($statistics['cpc'] ?? 0, 2),
            'frecuencia_media' => number_format($statistics['frequency'] ?? 0, 2),
            'alcance_neto' => number_format($statistics['reach'] ?? 0)
        ];
        
        $response = Http::timeout(60)->post($webappUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $slideIndex,
            'slide_data' => [
                'title' => $ad['name'],
                'metrics' => $metrics,
                'image_url' => $ad['image_url'] ?? null,
                'image_local_path' => $ad['image_local_path'] ?? null,
                'type' => 'ad'
            ]
        ]);
        
        if ($response->successful()) {
            $this->info("✅ Slide {$slideIndex} creado");
        } else {
            $this->error("❌ Error en slide {$slideIndex}");
        }
    }
    
    private function createMetricsSlide($webappUrl, $presentationId, $slideIndex, $facebookData)
    {
        // Preparar métricas agregadas por marca
        $brandMetrics = [];
        foreach ($facebookData['brands'] as $brand) {
            $totalReach = 0;
            $totalImpressions = 0;
            $totalClicks = 0;
            $totalSpend = 0;
            
            foreach ($brand['ads'] as $ad) {
                $stats = $ad['statistics'] ?? [];
                $totalReach += $stats['reach'] ?? 0;
                $totalImpressions += $stats['impressions'] ?? 0;
                $totalClicks += $stats['clicks'] ?? 0;
                $totalSpend += $stats['spend'] ?? 0;
            }
            
            $brandMetrics[$brand['name']] = [
                'alcance' => number_format($totalReach),
                'impresiones' => number_format($totalImpressions),
                'clicks' => number_format($totalClicks),
                'importe_gastado' => '$' . number_format($totalSpend, 2),
                'ctr' => $totalImpressions > 0 ? number_format(($totalClicks / $totalImpressions) * 100, 2) . '%' : '0%'
            ];
        }
        
        $response = Http::timeout(60)->post($webappUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $slideIndex,
            'slide_data' => [
                'title' => 'MÉTRICAS POR MARCA',
                'brand_metrics' => $brandMetrics,
                'type' => 'metrics_summary'
            ]
        ]);
        
        if ($response->successful()) {
            $this->info("✅ Slide {$slideIndex} creado");
        } else {
            $this->error("❌ Error en slide {$slideIndex}");
        }
    }
}
