<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\PdfReportService;

class TestPdfData extends Command
{
    protected $signature = 'test:pdf-data {reportId}';
    protected $description = 'Prueba los datos que se envían al template PDF';

    public function handle()
    {
        $reportId = $this->argument('reportId');
        $report = Report::findOrFail($reportId);
        
        $this->info("🔍 Verificando datos para reporte: {$report->name}");
        
        $pdfService = new PdfReportService();
        
        // Obtener datos de Facebook
        $facebookData = $pdfService->getFacebookDataByFanPages($report);
        
        $this->info("\n📊 Datos obtenidos:");
        $this->info("Total Fan Pages: " . count($facebookData['fan_pages']));
        $this->info("Total Anuncios: " . $facebookData['total_ads']);
        
        foreach ($facebookData['fan_pages'] as $index => $fanPage) {
            $this->info("\n🏢 Fan Page " . ($index + 1) . ": " . $fanPage['page_name']);
            $this->info("   - Total Ads: {$fanPage['total_ads']}");
            $this->info("   - Total Reach: " . number_format($fanPage['total_reach']));
            $this->info("   - Total Impressions: " . number_format($fanPage['total_impressions']));
            $this->info("   - Total Spend: $" . number_format($fanPage['total_spend'], 2));
            $this->info("   - Anuncios: " . count($fanPage['ads']));
            
            foreach ($fanPage['ads'] as $adIndex => $ad) {
                $this->info("     📱 Ad " . ($adIndex + 1) . ": " . $ad['ad_name']);
                $this->info("        - ID: {$ad['ad_id']}");
                $this->info("        - Reach: " . number_format($ad['reach']));
                $this->info("        - Image URL: " . ($ad['ad_image_url'] ?? 'No disponible'));
            }
        }
        
        $this->info("\n✅ Verificación completada");
    }
}
