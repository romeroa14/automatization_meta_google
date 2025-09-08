<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActiveCampaign;
use App\Services\CampaignReconciliationService;

class TestCampaignDatesExtraction extends Command
{
    protected $signature = 'test:campaign-dates-extraction';
    protected $description = 'Probar la extracción de fechas de inicio y final de campañas desde ActiveCampaign';

    public function handle()
    {
        $this->info('📅 Probando extracción de fechas de campañas...');
        
        // Obtener algunas campañas activas
        $activeCampaigns = ActiveCampaign::limit(3)->get();
        
        if ($activeCampaigns->isEmpty()) {
            $this->error('❌ No se encontraron campañas activas');
            return;
        }
        
        foreach ($activeCampaigns as $campaign) {
            $this->info("\n📊 Campaña: {$campaign->meta_campaign_name}");
            $this->info("   ID: {$campaign->meta_campaign_id}");
            
            // Extraer fechas usando el servicio
            $service = new CampaignReconciliationService();
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('extractCampaignInfo');
            $method->setAccessible(true);
            
            $campaignInfo = $method->invoke($service, $campaign);
            
            $this->info("   📅 Fecha de inicio: " . ($campaignInfo['start_date'] ?? 'No disponible'));
            $this->info("   📅 Fecha de final: " . ($campaignInfo['end_date'] ?? 'No disponible'));
            $this->info("   ⏱️  Duración: {$campaignInfo['duration_days']} días");
            $this->info("   💰 Presupuesto diario: $" . number_format($campaignInfo['daily_budget'], 2));
            $this->info("   💰 Presupuesto total: $" . number_format($campaignInfo['total_budget'], 2));
            $this->info("   👤 Cliente: {$campaignInfo['client_name']}");
            
            // Verificar fechas directas del modelo
            $this->info("\n   🔍 Fechas directas del modelo:");
            $this->info("   📅 campaign_start_time: " . ($campaign->campaign_start_time?->format('Y-m-d H:i:s') ?? 'null'));
            $this->info("   📅 campaign_stop_time: " . ($campaign->campaign_stop_time?->format('Y-m-d H:i:s') ?? 'null'));
            $this->info("   📅 adset_start_time: " . ($campaign->adset_start_time?->format('Y-m-d H:i:s') ?? 'null'));
            $this->info("   📅 adset_stop_time: " . ($campaign->adset_stop_time?->format('Y-m-d H:i:s') ?? 'null'));
        }
        
        $this->info("\n✅ Prueba completada");
        $this->info("💡 Las fechas se extraen automáticamente al crear transacciones contables");
    }
}
