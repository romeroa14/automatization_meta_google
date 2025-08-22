<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;

class TestAdsByPage extends Command
{
    protected $signature = 'facebook:test-ads-by-page {account_id} {page_id?}';
    protected $description = 'Probar obtención de anuncios filtrados por página específica';

    public function handle()
    {
        $accountId = $this->argument('account_id');
        $pageId = $this->argument('page_id');

        $account = FacebookAccount::find($accountId);
        if (!$account) {
            $this->error("No se encontró la cuenta de Facebook con ID: {$accountId}");
            return 1;
        }

        $this->info("🔍 Probando obtención de anuncios por página...");
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

            // 1. Obtener todas las páginas disponibles
            $this->info("\n📄 Obteniendo páginas disponibles...");
            $pagesUrl = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$token}";
            $pagesResponse = file_get_contents($pagesUrl);
            $pagesData = json_decode($pagesResponse, true);
            
            $this->info("Total páginas encontradas: " . count($pagesData['data']));
            
            if ($pageId) {
                $pageFound = false;
                foreach ($pagesData['data'] as $page) {
                    if ($page['id'] == $pageId) {
                        $this->info("✅ Página encontrada: {$page['name']} ({$page['category']})");
                        $pageFound = true;
                        break;
                    }
                }
                if (!$pageFound) {
                    $this->error("❌ La página {$pageId} no fue encontrada en las páginas disponibles");
                    return 1;
                }
            }

            // 2. Obtener anuncios de la cuenta publicitaria
            $this->info("\n🎯 Obteniendo anuncios de la cuenta publicitaria...");
            $baseUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads";
            $fields = 'id,name,campaign_id,creative';
            $params = [
                'fields' => $fields,
                'limit' => 250,
                'access_token' => $token
            ];
            
            $url = $baseUrl . '?' . http_build_query($params);
            $response = file_get_contents($url);
            $adsData = json_decode($response, true);
            
            $this->info("Total anuncios encontrados: " . count($adsData['data']));

            // 3. Analizar anuncios y sus creativos
            $this->info("\n🔍 Analizando anuncios y sus páginas...");
            $adsByPage = [];
            $adsWithoutPage = 0;
            $adsWithPage = 0;

            foreach ($adsData['data'] as $ad) {
                if (isset($ad['creative']['id'])) {
                    $creativeId = $ad['creative']['id'];
                    $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$token}";
                    
                    try {
                        $creativeResponse = file_get_contents($creativeUrl);
                        $creativeData = json_decode($creativeResponse, true);
                        
                        if (isset($creativeData['object_story_spec']['page_id'])) {
                            $adPageId = $creativeData['object_story_spec']['page_id'];
                            $adsWithPage++;
                            
                            if (!isset($adsByPage[$adPageId])) {
                                $adsByPage[$adPageId] = [];
                            }
                            $adsByPage[$adPageId][] = $ad;
                        } else {
                            $adsWithoutPage++;
                        }
                    } catch (\Exception $e) {
                        $this->warn("Error obteniendo creativo {$creativeId}: " . $e->getMessage());
                        $adsWithoutPage++;
                    }
                } else {
                    $adsWithoutPage++;
                }
            }

            $this->info("Anuncios con página identificada: {$adsWithPage}");
            $this->info("Anuncios sin página identificada: {$adsWithoutPage}");

            // 4. Mostrar distribución por páginas
            $this->info("\n📊 Distribución de anuncios por páginas:");
            foreach ($adsByPage as $pageId => $ads) {
                $pageName = "Página {$pageId}";
                foreach ($pagesData['data'] as $page) {
                    if ($page['id'] == $pageId) {
                        $pageName = $page['name'];
                        break;
                    }
                }
                $this->info("  📄 {$pageName} ({$pageId}): " . count($ads) . " anuncios");
            }

            // 5. Si se especificó una página, mostrar solo esos anuncios
            if ($pageId) {
                $this->info("\n🎯 Anuncios de la página específica ({$pageId}):");
                if (isset($adsByPage[$pageId])) {
                    foreach ($adsByPage[$pageId] as $ad) {
                        $this->info("  📢 {$ad['name']} (ID: {$ad['id']}) - Campaña: {$ad['campaign_id']}");
                    }
                    $this->info("Total anuncios de la página: " . count($adsByPage[$pageId]));
                } else {
                    $this->warn("No se encontraron anuncios para la página {$pageId}");
                }
            } else {
                // Mostrar resumen general
                $this->info("\n📊 Resumen general:");
                $this->info("  Total anuncios: " . count($adsData['data']));
                $this->info("  Anuncios con página: {$adsWithPage}");
                $this->info("  Anuncios sin página: {$adsWithoutPage}");
                $this->info("  Páginas con anuncios: " . count($adsByPage));
            }

            $this->info("\n✅ Análisis completado exitosamente");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
