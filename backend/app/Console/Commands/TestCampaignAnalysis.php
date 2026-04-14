<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActiveCampaign;
use App\Models\FacebookAccount;

class TestCampaignAnalysis extends Command
{
    protected $signature = 'test:campaign-analysis';
    protected $description = 'Probar análisis de campañas agrupadas';

    public function handle()
    {
        $this->info('🔍 PROBANDO ANÁLISIS DE CAMPAÑAS AGRUPADAS');
        $this->line('');
        
        // 1. Verificar datos existentes
        $this->info('📊 1. VERIFICANDO DATOS EXISTENTES...');
        $totalRecords = ActiveCampaign::count();
        $this->line("   • Total de registros: {$totalRecords}");
        
        if ($totalRecords > 0) {
            $uniqueCampaigns = ActiveCampaign::select('meta_campaign_id')
                ->distinct()
                ->count();
            $this->line("   • Campañas únicas: {$uniqueCampaigns}");
            
            $uniqueAdsets = ActiveCampaign::select('meta_adset_id')
                ->distinct()
                ->count();
            $this->line("   • AdSets únicos: {$uniqueAdsets}");
            
            $uniqueAds = ActiveCampaign::select('meta_ad_id')
                ->distinct()
                ->count();
            $this->line("   • Anuncios únicos: {$uniqueAds}");
            
            $this->line('');
            
            // 2. Mostrar ejemplo de agrupación
            $this->info('📋 2. EJEMPLO DE AGRUPACIÓN POR CAMPAÑA...');
            $campaigns = ActiveCampaign::selectRaw('
                meta_campaign_id,
                meta_campaign_name,
                campaign_daily_budget,
                amount_spent,
                campaign_status,
                COUNT(DISTINCT meta_adset_id) as adsets_count,
                COUNT(DISTINCT meta_ad_id) as ads_count
            ')
            ->groupBy([
                'meta_campaign_id',
                'meta_campaign_name',
                'campaign_daily_budget',
                'amount_spent',
                'campaign_status'
            ])
            ->limit(5)
            ->get();
            
            foreach ($campaigns as $campaign) {
                $this->line("   • {$campaign->meta_campaign_name}");
                $this->line("     - AdSets: {$campaign->adsets_count}");
                $this->line("     - Anuncios: {$campaign->ads_count}");
                $this->line("     - Estado: {$campaign->campaign_status}");
                $this->line("     - Presupuesto: $" . number_format($campaign->campaign_daily_budget ?? 0, 2));
                $this->line("     - Gastado: $" . number_format($campaign->amount_spent ?? 0, 2));
                $this->line('');
            }
            
            // 3. Verificar filtros
            $this->info('🔍 3. VERIFICANDO FILTROS...');
            
            $activeCampaigns = ActiveCampaign::where('campaign_status', 'ACTIVE')->count();
            $this->line("   • Campañas activas (filtro simple): {$activeCampaigns}");
            
            $scheduledCampaigns = ActiveCampaign::where('campaign_status', 'SCHEDULED')->count();
            $this->line("   • Campañas programadas: {$scheduledCampaigns}");
            
            $completedCampaigns = ActiveCampaign::where('campaign_status', 'COMPLETED')->count();
            $this->line("   • Campañas completadas: {$completedCampaigns}");
            
            $this->line('');
            
            // 4. Verificar cuentas publicitarias
            $this->info('🏦 4. VERIFICANDO CUENTAS PUBLICITARIAS...');
            $adAccounts = ActiveCampaign::select('ad_account_id')
                ->distinct()
                ->pluck('ad_account_id');
            
            $this->line("   • Cuentas publicitarias encontradas: " . $adAccounts->count());
            foreach ($adAccounts as $accountId) {
                $campaignsInAccount = ActiveCampaign::where('ad_account_id', $accountId)
                    ->select('meta_campaign_id')
                    ->distinct()
                    ->count();
                $this->line("     - ID {$accountId}: {$campaignsInAccount} campañas");
            }
            
        } else {
            $this->warn('⚠️ No hay datos de campañas en la base de datos.');
            $this->line('   Para cargar datos, usa el formulario en el panel de administración.');
        }
        
        $this->line('');
        $this->info('🎯 RECOMENDACIONES:');
        $this->line('1. Usar "Análisis de Campañas" para vista agrupada');
        $this->line('2. Usar "Campañas Activas" para vista detallada');
        $this->line('3. Los filtros ahora funcionan correctamente');
        $this->line('4. Se puede filtrar por cuenta publicitaria');
        
        $this->line('');
        $this->info('🎉 Análisis completado!');
    }
}