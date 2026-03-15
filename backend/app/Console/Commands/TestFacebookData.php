<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Log;

class TestFacebookData extends Command
{
    protected $signature = 'test:facebook-data {account_name?}';
    protected $description = 'Prueba la obtención de datos de Facebook para diagnosticar problemas';

    public function handle()
    {
        $accountName = $this->argument('account_name') ?? 'VISDOI';
        
        $this->info("🔍 Probando datos de Facebook para cuenta: {$accountName}");
        
        try {
            // Obtener la cuenta de Facebook
            $fbAccount = FacebookAccount::where('account_name', $accountName)->first();
            
            if (!$fbAccount) {
                $this->error("❌ No se encontró la cuenta: {$accountName}");
                return 1;
            }
            
            $this->info("✅ Cuenta encontrada: {$fbAccount->account_name}");
            
            // Configurar Facebook API
            Api::init(
                $fbAccount->app_id,
                $fbAccount->app_secret,
                $fbAccount->access_token
            );
            
            // Usar la cuenta publicitaria específica
            $adAccountId = $fbAccount->selected_ad_account_id ?: $fbAccount->account_id;
            $account = new AdAccount('act_' . $adAccountId);
            
            $this->info("📊 Usando cuenta publicitaria: act_{$adAccountId}");
            
            // 1. Probar obtención de campañas
            $this->info("\n🎯 Probando obtención de campañas...");
            $this->testCampaigns($account);
            
            // 2. Probar obtención de anuncios
            $this->info("\n📢 Probando obtención de anuncios...");
            $this->testAds($account);
            
            // 3. Probar insights con diferentes períodos
            $this->info("\n📈 Probando insights con diferentes períodos...");
            $this->testInsights($account);
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    private function testCampaigns(AdAccount $account)
    {
        try {
            $campaigns = $account->getCampaigns(['id', 'name', 'status', 'objective']);
            $this->info("✅ Campañas encontradas: " . count($campaigns));
            
            foreach ($campaigns as $campaign) {
                $this->line("  - ID: {$campaign->id}, Nombre: {$campaign->name}, Estado: {$campaign->status}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo campañas: " . $e->getMessage());
        }
    }
    
    private function testAds(AdAccount $account)
    {
        try {
            $ads = $account->getAds(['id', 'name', 'status', 'campaign_id']);
            $this->info("✅ Anuncios encontrados: " . count($ads));
            
            $count = 0;
            foreach ($ads as $ad) {
                if ($count < 10) { // Mostrar solo los primeros 10
                    $this->line("  - ID: {$ad->id}, Nombre: {$ad->name}, Estado: {$ad->status}");
                }
                $count++;
            }
            
            if (count($ads) > 10) {
                $this->line("  ... y " . (count($ads) - 10) . " más");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error obteniendo anuncios: " . $e->getMessage());
        }
    }
    
    private function testInsights(AdAccount $account)
    {
        $periods = [
            '7 días' => ['since' => date('Y-m-d', strtotime('-7 days')), 'until' => date('Y-m-d')],
            '30 días' => ['since' => date('Y-m-d', strtotime('-30 days')), 'until' => date('Y-m-d')],
            '90 días' => ['since' => date('Y-m-d', strtotime('-90 days')), 'until' => date('Y-m-d')],
        ];
        
        foreach ($periods as $label => $timeRange) {
            try {
                $this->info("  📅 Probando período: {$label}");
                
                $params = [
                    'level' => 'ad',
                    'time_range' => $timeRange,
                ];
                
                $insights = $account->getInsights(['ad_id', 'ad_name', 'impressions', 'clicks'], $params);
                $this->info("    ✅ Insights obtenidos: " . count($insights));
                
                if (count($insights) > 0) {
                    $sample = $insights[0];
                    $this->line("    📊 Muestra - Anuncio: {$sample->ad_name}, Impresiones: {$sample->impressions}, Clicks: {$sample->clicks}");
                }
                
            } catch (\Exception $e) {
                $this->error("    ❌ Error con período {$label}: " . $e->getMessage());
            }
        }
    }
}
