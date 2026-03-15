<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\PdfReportService;

class TestPdfData extends Command
{
    protected $signature = 'test:pdf-data';
    protected $description = 'Prueba los datos que se pasan al template PDF';

    public function handle()
    {
        $this->info("🧪 Probando datos del template PDF...");
        
        // Obtener el primer reporte
        $report = Report::first();
        
        if (!$report) {
            $this->error("❌ No hay reportes en la base de datos");
            return;
        }
        
        $this->info("📄 Reporte encontrado: {$report->name}");
        
        // Crear instancia del servicio
        $pdfService = new PdfReportService();
        
        // Obtener datos de Facebook
        $this->info("\n📊 Obteniendo datos de Facebook...");
        $facebookData = $pdfService->getFacebookDataByFanPages($report);
        
        $this->info("✅ Datos obtenidos:");
        $this->info("   - Fan Pages: " . count($facebookData['fan_pages']));
        $this->info("   - Total Anuncios: " . $facebookData['total_ads']);
        $this->info("   - Total Alcance: " . $facebookData['total_reach']);
        
        // Preparar datos para el template
        $reportData = [
            'report' => $report,
            'facebook_data' => $facebookData,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'period' => [
                'start' => $report->period_start->format('d/m/Y'),
                'end' => $report->period_end->format('d/m/Y'),
            ],
        ];
        
        $this->info("\n📋 Datos que se pasan al template:");
        $this->info("   - Report Name: " . $reportData['report']->name);
        $this->info("   - Generated At: " . $reportData['generated_at']);
        $this->info("   - Period Start: " . $reportData['period']['start']);
        $this->info("   - Period End: " . $reportData['period']['end']);
        $this->info("   - Facebook Data Keys: " . implode(', ', array_keys($reportData['facebook_data'])));
        
        // Verificar si hay datos de fan pages
        if (empty($facebookData['fan_pages'])) {
            $this->warn("⚠️  No hay datos de fan pages. Esto puede causar que la portada no se muestre correctamente.");
        } else {
            $this->info("\n📄 Fan Pages encontradas:");
            foreach ($facebookData['fan_pages'] as $index => $fanPage) {
                $this->info("   " . ($index + 1) . ". {$fanPage['page_name']} - {$fanPage['total_ads']} anuncios");
            }
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
