<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\FacebookDataForSlidesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestExactReportData extends Command
{
    protected $signature = 'slides:test-exact-data {report_id?}';
    protected $description = 'Prueba con datos exactos del reporte';

    public function handle()
    {
        $this->info('🧪 Probando con datos exactos del reporte...');
        
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
            
            // Probar con la primera campaña
            if (!empty($facebookData['brands']) && !empty($facebookData['brands'][0]['campaigns'])) {
                $firstCampaign = $facebookData['brands'][0]['campaigns'][0];
                
                $this->info('📄 Probando con primera campaña: ' . $firstCampaign['name']);
                
                // Preparar métricas para los 14 shapes
                $statistics = $firstCampaign['statistics'] ?? [];
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
                
                $slideResponse = Http::timeout(60)->post($webappUrl, [
                    'action' => 'create_slide',
                    'presentation_id' => $presentationId,
                    'slide_index' => 1,
                    'slide_data' => [
                        'title' => $firstCampaign['name'],
                        'metrics' => $metrics
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
                        
                        // Mostrar los datos que se enviaron
                        $this->newLine();
                        $this->info('📋 Datos enviados:');
                        $this->line("  - Título: {$firstCampaign['name']}");
                        $this->line("  - ID: {$firstCampaign['id']}");
                        $this->line("  - Impresiones: " . number_format($firstCampaign['statistics']['impressions'] ?? 0));
                        $this->line("  - Clicks: " . number_format($firstCampaign['statistics']['clicks'] ?? 0));
                        $this->line("  - CTR: " . number_format($firstCampaign['statistics']['ctr'] ?? 0, 2) . '%');
                    } else {
                        $this->error('❌ Error en respuesta de diapositiva');
                    }
                } else {
                    $this->error('❌ Error creando diapositiva');
                }
            } else {
                $this->error('❌ No hay campañas disponibles para probar');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
