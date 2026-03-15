<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;

class TestPdfButton extends Command
{
    protected $signature = 'test:pdf-button';
    protected $description = 'Prueba la funcionalidad del botón PDF';

    public function handle()
    {
        $this->info("🧪 Probando funcionalidad del botón PDF...");
        
        // Obtener el primer reporte
        $report = Report::first();
        
        if (!$report) {
            $this->error("❌ No hay reportes en la base de datos");
            return;
        }
        
        $this->info("📄 Reporte encontrado: {$report->name}");
        $this->info("📊 Estado actual:");
        $this->info("   - PDF Generado: " . ($report->pdf_generated ? '✅ Sí' : '❌ No'));
        $this->info("   - PDF URL: " . ($report->pdf_url ?? 'N/A'));
        
        // Simular que se generó un PDF
        $this->info("\n🔄 Simulando generación de PDF...");
        
        $report->update([
            'pdf_generated' => true,
            'pdf_url' => '/storage/reports/report_' . $report->id . '.pdf',
            'generated_at' => now(),
        ]);
        
        $this->info("✅ PDF simulado generado");
        $this->info("📊 Nuevo estado:");
        $this->info("   - PDF Generado: " . ($report->pdf_generated ? '✅ Sí' : '❌ No'));
        $this->info("   - PDF URL: " . ($report->pdf_url ?? 'N/A'));
        
        $this->info("\n🎯 Ahora el botón debería mostrar 'Ver PDF' en lugar de 'Generar PDF'");
        
        $this->info("\n✅ Prueba completada");
    }
}
