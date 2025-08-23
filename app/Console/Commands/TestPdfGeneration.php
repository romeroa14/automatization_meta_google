<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\PdfReportService;

class TestPdfGeneration extends Command
{
    protected $signature = 'test:pdf-generation {reportId}';
    protected $description = 'Prueba la generación de reportes en PDF';

    public function handle()
    {
        $reportId = $this->argument('reportId');
        $report = Report::findOrFail($reportId);
        
        $this->info("🔍 Probando generación de PDF para reporte: {$report->name}");
        
        $pdfService = new PdfReportService();
        $result = $pdfService->generateReport($report);
        
        if ($result['success']) {
            $this->info("✅ PDF generado exitosamente");
            $this->info("📁 Archivo: {$result['file_path']}");
            $this->info("🔗 URL: {$result['file_url']}");
            $this->info("📄 Nombre: {$result['filename']}");
            $this->info("📊 Slides: {$result['slides_count']}");
            
            $this->info("\n🎯 Para ver el PDF, visita: {$result['file_url']}");
        } else {
            $this->error("❌ Error generando PDF: {$result['error']}");
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
