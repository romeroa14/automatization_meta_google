<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\PdfReportService;

class TestPdfGeneration extends Command
{
    protected $signature = 'test:pdf-generation';
    protected $description = 'Genera un PDF real para probar la portada';

    public function handle()
    {
        $this->info("🚀 Generando PDF real para probar la portada...");
        
        // Obtener el primer reporte
        $report = Report::first();
        
        if (!$report) {
            $this->error("❌ No hay reportes en la base de datos");
            return;
        }
        
        $this->info("📄 Reporte encontrado: {$report->name}");
        
        // Crear instancia del servicio
        $pdfService = new PdfReportService();
        
        // Generar el PDF
        $this->info("\n📊 Generando PDF...");
        $result = $pdfService->generateReport($report);
        
        if ($result['success']) {
            $this->info("✅ PDF generado exitosamente!");
            $this->info("📁 Archivo: {$result['file_path']}");
            $this->info("🌐 URL: {$result['file_url']}");
            $this->info("📄 Páginas: {$result['slides_count']}");
            
            // Actualizar el reporte
            $report->update([
                'pdf_generated' => true,
                'pdf_url' => $result['file_url'],
                'generated_at' => now(),
            ]);
            
            $this->info("\n🎯 La portada debería mostrarse en la primera página del PDF");
            $this->info("📋 Verifica que contenga:");
            $this->info("   - Título grande: {$report->name}");
            $this->info("   - Período: {$report->period_start->format('d/m/Y')} - {$report->period_end->format('d/m/Y')}");
            $this->info("   - Tu nombre: Alfredo Romero");
            $this->info("   - Tu sitio web: alfredoromero.io");
            
        } else {
            $this->error("❌ Error generando PDF: " . $result['error']);
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
