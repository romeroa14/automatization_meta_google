<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report;
use App\Models\FacebookAccount;
use App\Services\GoogleSlidesReportService;
use Illuminate\Support\Facades\Log;

class TestFacebookDataRetrieval extends Command
{
    protected $signature = 'test:facebook-data {report_id}';
    protected $description = 'Test Facebook data retrieval for a specific report';

    public function handle()
    {
        $reportId = $this->argument('report_id');
        $report = Report::find($reportId);
        
        if (!$report) {
            $this->error("Reporte con ID {$reportId} no encontrado");
            return 1;
        }

        $this->info("🔍 Probando obtención de datos para reporte: {$report->name}");
        $this->info("📅 Período: {$report->period_start} - {$report->period_end}");
        
        // Verificar cuentas seleccionadas
        $accountIds = $report->selected_facebook_accounts ?? [];
        $this->info("📊 Cuentas seleccionadas: " . implode(', ', $accountIds));
        
        if (empty($accountIds)) {
            $this->error("❌ No hay cuentas de Facebook seleccionadas");
            return 1;
        }

        $accounts = FacebookAccount::whereIn('id', $accountIds)->get();
        
        foreach ($accounts as $account) {
            $this->info("\n🏢 Cuenta: {$account->account_name}");
            $this->info("   - App ID: {$account->app_id}");
            $this->info("   - Ad Account ID: {$account->selected_ad_account_id}");
            $this->info("   - Page ID: {$account->selected_page_id}");
            $this->info("   - Anuncios configurados: " . implode(', ', $account->selected_ad_ids ?? []));
            
            if (empty($account->selected_ad_ids)) {
                $this->warn("   ⚠️ No hay anuncios configurados para esta cuenta");
                continue;
            }

            // Probar obtención de datos
            try {
                $service = new GoogleSlidesReportService();
                $facebookData = $service->getFacebookDataByFanPages($report);
                
                $this->info("   ✅ Datos obtenidos:");
                $this->info("      - Fan Pages: " . count($facebookData['fan_pages']));
                $this->info("      - Total anuncios: {$facebookData['total_ads']}");
                
                foreach ($facebookData['fan_pages'] as $fanPage) {
                    $this->info("      📄 Fan Page: {$fanPage['page_name']}");
                    $this->info("         - Anuncios: " . count($fanPage['ads']));
                    $this->info("         - Alcance total: " . number_format($fanPage['total_reach']));
                    $this->info("         - Impresiones totales: " . number_format($fanPage['total_impressions']));
                    $this->info("         - Clicks totales: " . number_format($fanPage['total_clicks']));
                    $this->info("         - Gasto total: $" . number_format($fanPage['total_spend'], 2));
                    
                    foreach ($fanPage['ads'] as $ad) {
                        $this->info("         📊 Anuncio {$ad['ad_id']}: {$ad['ad_name']}");
                        $this->info("            - Alcance: " . number_format($ad['reach']));
                        $this->info("            - Impresiones: " . number_format($ad['impressions']));
                        $this->info("            - Clicks: " . number_format($ad['clicks']));
                        $this->info("            - CTR: " . number_format($ad['ctr'], 2) . '%');
                        $this->info("            - Gasto: $" . number_format($ad['spend'], 2));
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error obteniendo datos: " . $e->getMessage());
                Log::error("Error en test de Facebook data: " . $e->getMessage());
            }
        }

        $this->info("\n✅ Prueba completada");
        return 0;
    }
}
