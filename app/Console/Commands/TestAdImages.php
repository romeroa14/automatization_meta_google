<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Services\GoogleSlidesReportService;

class TestAdImages extends Command
{
    protected $signature = 'test:ad-images {reportId}';
    protected $description = 'Prueba la obtención de imágenes de anuncios';

    public function handle()
    {
        $reportId = $this->argument('reportId');
        $report = Report::findOrFail($reportId);
        
        $this->info("🔍 Probando obtención de imágenes para reporte: {$report->name}");
        
        $service = new GoogleSlidesReportService();
        $facebookData = $service->getFacebookDataByFanPages($report);
        
        $this->info("📊 Total de Fan Pages: " . count($facebookData['fan_pages']));
        
        foreach ($facebookData['fan_pages'] as $fanPage) {
            $this->info("\n🏢 Fan Page: {$fanPage['page_name']}");
            $this->info("📄 Total de anuncios: " . count($fanPage['ads']));
            
            foreach ($fanPage['ads'] as $ad) {
                $this->info("  📊 Anuncio: {$ad['ad_name']}");
                $this->info("    ID: {$ad['ad_id']}");
                
                if (!empty($ad['ad_image_url'])) {
                    $this->info("    🖼️  Imagen: {$ad['ad_image_url']}");
                } else {
                    $this->warn("    ❌ Sin imagen");
                }
            }
        }
        
        $this->info("\n✅ Prueba completada");
    }
}
