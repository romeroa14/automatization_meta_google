<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActiveCampaign;
use App\Models\FacebookAccount;

class LoadActiveCampaigns extends Command
{
    protected $signature = 'load:active-campaigns';
    protected $description = 'Cargar campañas activas desde Meta API';

    public function handle()
    {
        $this->info('🔄 Cargando campañas activas...');
        
        $account = FacebookAccount::first();
        if (!$account) {
            $this->error('❌ No se encontró cuenta de Facebook');
            return;
        }
        
        $this->info("📱 Cuenta: {$account->account_name}");
        
        try {
            // Usar la cuenta publicitaria seleccionada
            if (!$account->selected_ad_account_id) {
                $this->error('❌ No se ha configurado selected_ad_account_id');
                return;
            }
            
            $adAccountId = str_replace('act_', '', $account->selected_ad_account_id);
            $accountName = 'Cuenta ' . $adAccountId;
            $this->info("🎯 Usando cuenta publicitaria: {$accountName} (ID: {$adAccountId})");
            
            // Limpiar campañas existentes
            ActiveCampaign::query()->delete();
            $this->info('🧹 Campañas existentes eliminadas');
            
            // Cargar nuevas campañas
            $campaigns = ActiveCampaign::getActiveCampaignsHierarchy($account->id, $adAccountId);
            
            // Filtrar campañas recientes (últimos 2 años)
            $recentCampaigns = $campaigns->filter(function($campaign) {
                $startTime = $campaign->campaign_start_time;
                if (!$startTime) return false;
                
                // Solo campañas de los últimos 2 años
                return $startTime->isAfter(now()->subYears(2));
            });
            
            $this->info("📅 Campañas recientes (últimos 2 años): {$recentCampaigns->count()}");
            $campaigns = $recentCampaigns;
            $this->info("📊 Campañas encontradas: {$campaigns->count()}");
            
            if ($campaigns->isEmpty()) {
                $this->warn('⚠️  No se encontraron campañas activas');
                return;
            }
            
            // Guardar en base de datos
            foreach ($campaigns as $campaign) {
                $campaign->save();
            }
            
            $this->info('✅ Campañas guardadas en base de datos');
            
            // Mostrar resumen
            foreach ($campaigns->take(3) as $campaign) {
                $this->line("   📋 {$campaign->meta_campaign_name}");
                $this->line("      💰 Presupuesto diario: $" . number_format($campaign->campaign_daily_budget ?? $campaign->adset_daily_budget ?? 0, 2));
                $this->line("      📅 Inicio: " . ($campaign->campaign_start_time?->format('Y-m-d') ?? 'N/A'));
                $this->line("      📅 Final: " . ($campaign->campaign_stop_time?->format('Y-m-d') ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}
