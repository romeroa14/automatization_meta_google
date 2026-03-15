<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\GoogleSlidesReportService;
use Illuminate\Console\Command;

class TestSlideData extends Command
{
    protected $signature = 'slides:test-data {report_id : ID del reporte a probar}';
    protected $description = 'Prueba y muestra los datos que se envían a las diapositivas';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);

        if (!$report) {
            $this->error("❌ No se encontró el reporte con ID: {$reportId}");
            return 1;
        }

        $this->info("🧪 Probando datos del reporte: {$report->name}");
        $this->info("📅 Período: {$report->period_start->format('d/m/Y')} - {$report->period_end->format('d/m/Y')}");

        try {
            $service = new GoogleSlidesReportService();
            
            // Preparar los datos del reporte
            $reportData = $service->prepareReportData($report);
            
            $this->info("📊 Total de diapositivas a generar: " . count($reportData['slides']));
            $this->line("");

            // Mostrar cada diapositiva
            foreach ($reportData['slides'] as $index => $slide) {
                $this->info("📄 Diapositiva " . ($index + 1) . ": {$slide['type']}");
                $this->line("   Título: {$slide['title']}");
                $this->line("   Subtítulo: {$slide['subtitle']}");
                
                if (isset($slide['content']) && is_array($slide['content'])) {
                    $this->line("   Contenido:");
                    foreach ($slide['content'] as $key => $value) {
                        $this->line("     - {$key}: {$value}");
                    }
                }
                
                if (isset($slide['image'])) {
                    $this->line("   Imagen: {$slide['image']['url']}");
                }
                
                $this->line("");
            }

            // Mostrar ejemplo de datos enviados al Google Apps Script
            $this->info("🔧 Ejemplo de datos enviados al Google Apps Script:");
            $this->line("");
            
            $exampleSlide = $reportData['slides'][2] ?? $reportData['slides'][0];
            $exampleData = [
                'action' => 'create_slide',
                'presentation_id' => 'EXAMPLE_PRESENTATION_ID',
                'slide_index' => 2,
                'slide_data' => $exampleSlide
            ];
            
            $this->line(json_encode($exampleData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
