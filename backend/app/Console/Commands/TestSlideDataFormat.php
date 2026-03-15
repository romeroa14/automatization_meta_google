<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\GoogleSlidesReportService;

class TestSlideDataFormat extends Command
{
    protected $signature = 'test:slide-format {report_id}';
    protected $description = 'Test the slide data format being sent to Google Apps Script';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);
        
        if (!$report) {
            $this->error("Reporte con ID {$reportId} no encontrado");
            return 1;
        }

        $this->info("🔍 Probando formato de datos para reporte: {$report->name}");
        
        try {
            $service = new GoogleSlidesReportService();
            $reportData = $service->prepareReportDataOptimized($report);
            
            $this->info("✅ Datos preparados exitosamente");
            $this->info("📊 Total de slides: " . count($reportData['slides']));
            
            foreach ($reportData['slides'] as $index => $slide) {
                $this->info("\n📄 Slide {$index}: {$slide['type']}");
                $this->info("   Título: {$slide['title']}");
                
                if (isset($slide['metrics'])) {
                    $this->info("   Métricas:");
                    foreach ($slide['metrics'] as $key => $value) {
                        $this->info("     - {$key}: {$value}");
                    }
                }
                
                if (isset($slide['followers'])) {
                    $this->info("   Seguidores:");
                    foreach ($slide['followers'] as $platform => $count) {
                        $this->info("     - {$platform}: {$count}");
                    }
                }
            }
            
            // Mostrar ejemplo de datos que se enviarían a Google Apps Script
            $this->info("\n📤 Ejemplo de datos para Google Apps Script:");
            $this->info(json_encode($reportData, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        $this->info("\n✅ Prueba completada");
        return 0;
    }
}
