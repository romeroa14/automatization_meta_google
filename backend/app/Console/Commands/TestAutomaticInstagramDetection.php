<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActiveCampaign;
use App\Services\CampaignReconciliationService;

class TestAutomaticInstagramDetection extends Command
{
    protected $signature = 'test:automatic-instagram-detection';
    protected $description = 'Probar la detección automática de nombres de Instagram en transacciones contables';

    public function handle()
    {
        $this->info('🔍 Probando detección automática de nombres de Instagram...');
        
        // Obtener algunas campañas activas
        $activeCampaigns = ActiveCampaign::limit(3)->get();
        
        if ($activeCampaigns->isEmpty()) {
            $this->error('❌ No se encontraron campañas activas');
            return;
        }
        
        $service = new CampaignReconciliationService();
        
        foreach ($activeCampaigns as $campaign) {
            $this->info("\n📊 Campaña: {$campaign->meta_campaign_name}");
            $this->info("   ID: {$campaign->meta_campaign_id}");
            $this->info("   Ad ID: {$campaign->meta_ad_id}");
            
            try {
                // Usar reflexión para acceder al método privado
                $reflection = new \ReflectionClass($service);
                $method = $reflection->getMethod('extractClientName');
                $method->setAccessible(true);
                
                $clientName = $method->invoke($service, $campaign);
                
                $this->info("   ✅ Cliente detectado: {$clientName}");
                
                if ($clientName === 'Cliente Sin Identificar') {
                    $this->warn("   ⚠️  No se pudo detectar el nombre de Instagram");
                } else {
                    $this->info("   🎯 Nombre de Instagram obtenido exitosamente");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }
        }
        
        $this->info("\n✅ Prueba completada");
        $this->info("💡 La detección automática se ejecuta cuando se crean transacciones contables");
    }
}
