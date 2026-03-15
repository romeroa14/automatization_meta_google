<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;
use App\Models\AutomationTask;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Log;

class TestPageFiltering extends Command
{
    protected $signature = 'test:page-filtering {task_id?}';
    protected $description = 'Prueba el filtrado por fanpage específica';

    public function handle()
    {
        $taskId = $this->argument('task_id') ?? 1;
        
        $this->info("🔍 Probando filtrado por fanpage para tarea: {$taskId}");
        
        try {
            // Obtener la tarea de automatización
            $task = AutomationTask::find($taskId);
            
            if (!$task) {
                $this->error("❌ No se encontró la tarea: {$taskId}");
                return 1;
            }
            
            $this->info("✅ Tarea encontrada: {$task->name}");
            
            // Obtener la cuenta de Facebook
            $fbAccount = $task->facebookAccount;
            
            if (!$fbAccount) {
                $this->error("❌ No se encontró la cuenta de Facebook para la tarea");
                return 1;
            }
            
            $this->info("✅ Cuenta de Facebook: {$fbAccount->account_name}");
            
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
            
            // Probar el filtrado por fanpage
            $this->info("\n🎯 Probando filtrado por fanpage específica...");
            $this->testPageFiltering($account, $fbAccount);
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
    
    private function testPageFiltering($account, $fbAccount)
    {
        try {
            // Obtener todas las campañas
            $allCampaigns = $account->getCampaigns(['id', 'name', 'status']);
            $this->info("📋 Total de campañas en la cuenta publicitaria: " . count($allCampaigns));
            
            // Filtrar por fanpage específica
            $pageCampaigns = [];
            
            foreach ($allCampaigns as $campaign) {
                $campaignName = $campaign->name ?? '';
                
                if (stripos($campaignName, $fbAccount->account_name) !== false) {
                    $pageCampaigns[] = $campaign->id;
                    $this->info("✅ Campaña de {$fbAccount->account_name}: {$campaignName} (ID: {$campaign->id})");
                }
            }
            
            $this->info("\n🎯 Total de campañas de {$fbAccount->account_name}: " . count($pageCampaigns));
            
            if (empty($pageCampaigns)) {
                $this->warn("⚠️ No se encontraron campañas para la fanpage: {$fbAccount->account_name}");
                $this->info("📋 Campañas disponibles:");
                foreach ($allCampaigns as $campaign) {
                    $this->line("  - {$campaign->name} (ID: {$campaign->id})");
                }
            } else {
                // Probar insights solo para las campañas de la fanpage
                $this->info("\n📈 Probando insights solo para campañas de {$fbAccount->account_name}...");
                
                $params = [
                    'level' => 'ad',
                    'time_range' => [
                        'since' => date('Y-m-d', strtotime('-30 days')),
                        'until' => date('Y-m-d'),
                    ],
                    'filtering' => [
                        [
                            'field' => 'campaign.id',
                            'operator' => 'IN',
                            'value' => $pageCampaigns,
                        ],
                    ],
                ];
                
                $insights = $account->getInsights(['ad_id', 'ad_name', 'impressions', 'clicks'], $params);
                $this->info("✅ Insights obtenidos para campañas de {$fbAccount->account_name}: " . count($insights));
                
                if (count($insights) > 0) {
                    $this->info("📊 Muestra de datos:");
                    foreach (array_slice($insights, 0, 5) as $insight) {
                        $this->line("  - Anuncio: {$insight->ad_name}, Impresiones: {$insight->impressions}, Clicks: {$insight->clicks}");
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error en el filtrado: " . $e->getMessage());
        }
    }
}
