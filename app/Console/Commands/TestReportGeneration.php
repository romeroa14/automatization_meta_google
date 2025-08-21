<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\GoogleSlidesReportService;
use Illuminate\Console\Command;

class TestReportGeneration extends Command
{
    protected $signature = 'reports:test-generation {report_id? : ID del reporte a probar}';
    protected $description = 'Prueba la generación de reportes en Google Slides';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        
        if ($reportId) {
            $report = Report::find($reportId);
            if (!$report) {
                $this->error("❌ No se encontró el reporte con ID: {$reportId}");
                return 1;
            }
        } else {
            $report = Report::where('status', 'draft')->first();
            if (!$report) {
                $this->error("❌ No se encontró ningún reporte en estado 'draft'");
                return 1;
            }
        }
        
        $this->info("🧪 Probando generación de reporte: {$report->name}");
        $this->info("📅 Período: {$report->period_start->format('d/m/Y')} - {$report->period_end->format('d/m/Y')}");
        $this->info("📊 Marcas: {$report->total_brands}, Campañas: {$report->total_campaigns}");
        
        $service = new GoogleSlidesReportService();
        
        try {
            $this->info("🚀 Iniciando generación...");
            
            $result = $service->generateReport($report);
            
            if ($result['success']) {
                $this->info("✅ Reporte generado exitosamente!");
                $this->info("📄 Presentación ID: {$result['presentation_id']}");
                $this->info("🔗 URL: {$result['presentation_url']}");
                $this->info("📊 Diapositivas: {$result['slides_count']}");
                
                // Actualizar estado del reporte
                $service->updateReportStatus($report, $result);
                
                return 0;
            } else {
                $this->error("❌ Error generando reporte: {$result['error']}");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Excepción: " . $e->getMessage());
            return 1;
        }
    }
}
