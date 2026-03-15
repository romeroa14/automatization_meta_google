<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\FacebookDataForSlidesService;
use Illuminate\Console\Command;

class TestFacebookDataForSlides extends Command
{
    protected $signature = 'slides:test-facebook-data {report_id?}';
    protected $description = 'Prueba el servicio de datos de Facebook para slides';

    public function handle()
    {
        $this->info('🧪 Probando servicio de datos de Facebook para slides...');
        
        $reportId = $this->argument('report_id');
        
        if (!$reportId) {
            $report = Report::latest()->first();
            if (!$report) {
                $this->error('❌ No se encontró ningún reporte. Crea uno primero.');
                return 1;
            }
            $reportId = $report->id;
        }
        
        $report = Report::find($reportId);
        if (!$report) {
            $this->error("❌ No se encontró el reporte con ID: {$reportId}");
            return 1;
        }
        
        $this->info("📊 Probando reporte: {$report->name} (ID: {$report->id})");
        
        try {
            $service = new FacebookDataForSlidesService();
            $facebookData = $service->getFacebookDataForReport($report);
            
            $this->info('✅ Datos obtenidos exitosamente!');
            $this->newLine();
            
            // Mostrar estructura de datos
            $this->info('📋 Estructura de datos:');
            $this->line("  - Título: {$facebookData['title']}");
            $this->line("  - Subtítulo: {$facebookData['subtitle']}");
            $this->line("  - Período: {$facebookData['period']['start']} - {$facebookData['period']['end']}");
            $this->line("  - Marcas: " . count($facebookData['brands']));
            $this->line("  - Estadísticas generales: " . count($facebookData['statistics']));
            
            // Mostrar estadísticas generales
            $this->newLine();
            $this->info('📊 Estadísticas generales:');
            $stats = $facebookData['statistics'];
            $this->line("  - Impresiones: " . number_format($stats['impressions'] ?? 0));
            $this->line("  - Clicks: " . number_format($stats['clicks'] ?? 0));
            $this->line("  - Alcance: " . number_format($stats['reach'] ?? 0));
            $this->line("  - Interacciones: " . number_format($stats['total_interactions'] ?? 0));
            $this->line("  - CTR: " . number_format($stats['ctr'] ?? 0, 2) . '%');
            $this->line("  - Tasa de interacción: " . number_format($stats['interaction_rate'] ?? 0, 2) . '%');
            
            // Mostrar datos por marca
            $this->newLine();
            $this->info('🏷️ Datos por marca:');
            foreach ($facebookData['brands'] as $index => $brandData) {
                $this->line("  " . ($index + 1) . ". {$brandData['name']}:");
                $brandStats = $brandData['statistics'];
                $this->line("     - Campañas: " . ($brandStats['campaigns_count'] ?? 0));
                $this->line("     - Impresiones: " . number_format($brandStats['impressions'] ?? 0));
                $this->line("     - Clicks: " . number_format($brandStats['clicks'] ?? 0));
                $this->line("     - CTR: " . number_format($brandStats['ctr'] ?? 0, 2) . '%');
                
                // Mostrar campañas de esta marca
                foreach ($brandData['campaigns'] as $campaignIndex => $campaignData) {
                    $this->line("     - Campaña " . ($campaignIndex + 1) . ": {$campaignData['name']}");
                    $campaignStats = $campaignData['statistics'];
                    $this->line("       * Impresiones: " . number_format($campaignStats['impressions'] ?? 0));
                    $this->line("       * Clicks: " . number_format($campaignStats['clicks'] ?? 0));
                    $this->line("       * CTR: " . number_format($campaignStats['ctr'] ?? 0, 2) . '%');
                    $this->line("       * Interacciones: " . number_format($campaignStats['total_interactions'] ?? 0));
                    
                    if (!empty($campaignData['image_url']) || !empty($campaignData['image_local_path'])) {
                        $this->line("       * Tiene imagen: ✅");
                    } else {
                        $this->line("       * Tiene imagen: ❌");
                    }
                }
            }
            
            $this->newLine();
            $this->info('🎯 Datos listos para enviar a Google Slides!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo datos: " . $e->getMessage());
            $this->error("📋 Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
