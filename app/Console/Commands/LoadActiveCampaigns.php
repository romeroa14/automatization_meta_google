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
            // Obtener cuentas publicitarias
            $url = "https://graph.facebook.com/v18.0/me/adaccounts?limit=250&access_token={$account->access_token}";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if (!isset($data['data']) || empty($data['data'])) {
                $this->error('❌ No se encontraron cuentas publicitarias');
                return;
            }
            
            $adAccount = $data['data'][0]; // Usar la primera cuenta
            $adAccountId = str_replace('act_', '', $adAccount['id']);
            $accountName = $adAccount['name'] ?? 'Cuenta ' . $adAccountId;
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
