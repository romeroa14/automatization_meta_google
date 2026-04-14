<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;

class TestCampaignsByPage extends Command
{
    protected $signature = 'facebook:test-campaigns-by-page {account_id} {page_id?}';
    protected $description = 'Probar filtrado de campañas por página específica';

    public function handle()
    {
        $accountId = $this->argument('account_id');
        $pageId = $this->argument('page_id');

        $account = FacebookAccount::find($accountId);
        if (!$account) {
            $this->error("No se encontró la cuenta de Facebook con ID: {$accountId}");
            return 1;
        }

        $this->info("🔍 Probando filtrado de campañas por página...");
        $this->info("📱 Usando cuenta: {$account->account_name}");
        $this->info("💰 Cuenta publicitaria: {$account->selected_ad_account_id}");
        
        if ($pageId) {
            $this->info("📄 Página específica: {$pageId}");
        } else {
            $this->info("📄 Sin filtro de página (todas las páginas)");
        }

        try {
            $token = $account->access_token;
            $adAccountId = $account->selected_ad_account_id;

            // 1. Obtener todas las campañas de la cuenta
            $this->info("\n🎯 Obteniendo todas las campañas de la cuenta...");
            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status&limit=250&access_token={$token}";
            $campaignsResponse = file_get_contents($campaignsUrl);
            $campaignsData = json_decode($campaignsResponse, true);
            
            $this->info("Total campañas en la cuenta: " . count($campaignsData['data']));

            if ($pageId) {
                // 2. Si hay página seleccionada, filtrar campañas por página
                $this->info("\n🔍 Filtrando campañas por página {$pageId}...");
                
                // Obtener anuncios de la cuenta
                $adsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads?fields=id,name,campaign_id,creative&limit=250&access_token={$token}";
                $adsResponse = file_get_contents($adsUrl);
                $adsData = json_decode($adsResponse, true);
                
                $campaignsForPage = [];
                $adsForPage = [];
                
                if (isset($adsData['data'])) {
                    foreach ($adsData['data'] as $ad) {
                        if (isset($ad['creative']['id'])) {
                            $creativeId = $ad['creative']['id'];
                            $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$token}";
                            $creativeResponse = file_get_contents($creativeUrl);
                            $creativeData = json_decode($creativeResponse, true);
                            
                            if (isset($creativeData['object_story_spec']['page_id']) && 
                                $creativeData['object_story_spec']['page_id'] == $pageId) {
                                $campaignsForPage[$ad['campaign_id']] = true;
                                $adsForPage[] = $ad;
                            }
                        }
                    }
                }
                
                $this->info("Anuncios encontrados para la página: " . count($adsForPage));
                $this->info("Campañas únicas para la página: " . count($campaignsForPage));
                
                // Mostrar campañas filtradas
                $this->info("\n📊 Campañas que pertenecen a la página {$pageId}:");
                $filteredCampaigns = [];
                foreach ($campaignsData['data'] as $campaign) {
                    if ($campaign['status'] == 'ACTIVE' && isset($campaignsForPage[$campaign['id']])) {
                        $filteredCampaigns[] = $campaign;
                        $this->info("  ✅ {$campaign['name']} (ID: {$campaign['id']})");
                    }
                }
                
                $this->info("\n📊 Campañas que NO pertenecen a la página {$pageId}:");
                foreach ($campaignsData['data'] as $campaign) {
                    if ($campaign['status'] == 'ACTIVE' && !isset($campaignsForPage[$campaign['id']])) {
                        $this->info("  ❌ {$campaign['name']} (ID: {$campaign['id']})");
                    }
                }
                
                $this->info("\n📊 Resumen:");
                $this->info("  Total campañas activas: " . count(array_filter($campaignsData['data'], fn($c) => $c['status'] == 'ACTIVE')));
                $this->info("  Campañas filtradas por página: " . count($filteredCampaigns));
                
            } else {
                // 3. Si no hay página seleccionada, mostrar todas las campañas activas
                $this->info("\n📊 Todas las campañas activas:");
                $activeCampaigns = 0;
                foreach ($campaignsData['data'] as $campaign) {
                    if ($campaign['status'] == 'ACTIVE') {
                        $activeCampaigns++;
                        $this->info("  ✅ {$campaign['name']} (ID: {$campaign['id']})");
                    }
                }
                $this->info("\nTotal campañas activas: {$activeCampaigns}");
            }

            $this->info("\n✅ Análisis completado exitosamente");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
