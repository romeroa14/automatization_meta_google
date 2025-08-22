<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;

class TestNewHierarchy extends Command
{
    protected $signature = 'facebook:test-new-hierarchy {account_id}';
    protected $description = 'Probar la nueva jerarquía: Token → Cuentas Publicitarias → Fan Pages → Campañas → Anuncios';

    public function handle()
    {
        $accountId = $this->argument('account_id');

        $account = FacebookAccount::find($accountId);
        if (!$account) {
            $this->error("No se encontró la cuenta de Facebook con ID: {$accountId}");
            return 1;
        }

        $this->info("🔍 Probando nueva jerarquía completa...");
        $this->info("📱 Usando cuenta: {$account->account_name}");

        try {
            $token = $account->access_token;

            // 1. Obtener todas las cuentas publicitarias del token
            $this->info("\n💰 Paso 1: Obtener cuentas publicitarias del token...");
            $adAccountsUrl = "https://graph.facebook.com/v18.0/me/adaccounts?limit=250&access_token={$token}";
            $adAccountsResponse = file_get_contents($adAccountsUrl);
            $adAccountsData = json_decode($adAccountsResponse, true);
            
            $this->info("Total cuentas publicitarias encontradas: " . count($adAccountsData['data']));
            
            $this->info("\n📋 Cuentas publicitarias disponibles:");
            foreach ($adAccountsData['data'] as $adAccount) {
                $accountId = str_replace('act_', '', $adAccount['id']);
                $accountName = $adAccount['name'] ?? 'Cuenta ' . $accountId;
                $this->info("  - {$accountName} (ID: {$accountId})");
            }

            // 2. Obtener todas las páginas del token
            $this->info("\n📄 Paso 2: Obtener páginas del token...");
            $pagesUrl = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$token}";
            $pagesResponse = file_get_contents($pagesUrl);
            $pagesData = json_decode($pagesResponse, true);
            
            $this->info("Total páginas encontradas: " . count($pagesData['data']));
            
            $this->info("\n📋 Ejemplos de páginas disponibles:");
            for ($i = 0; $i < min(5, count($pagesData['data'])); $i++) {
                $page = $pagesData['data'][$i];
                $this->info("  - {$page['name']} (ID: {$page['id']}) - {$page['category']}");
            }

            // 3. Probar con una cuenta específica
            $testAdAccountId = '1124273537782021'; // Primera cuenta
            $this->info("\n🎯 Paso 3: Probar con cuenta publicitaria específica: {$testAdAccountId}");
            
            // Obtener campañas de esta cuenta
            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$testAdAccountId}/campaigns?fields=id,name,status&limit=250&access_token={$token}";
            $campaignsResponse = file_get_contents($campaignsUrl);
            $campaignsData = json_decode($campaignsResponse, true);
            
            $this->info("Total campañas en la cuenta {$testAdAccountId}: " . count($campaignsData['data']));
            
            // 4. Probar filtrado por página específica
            $testPageId = '692024630667546'; // Licencias Digitales y Algo Mas
            $this->info("\n🎯 Paso 4: Probar filtrado por página: Licencias Digitales y Algo Mas ({$testPageId})");
            
            // Obtener anuncios y filtrar por página
            $adsUrl = "https://graph.facebook.com/v18.0/act_{$testAdAccountId}/ads?fields=id,name,campaign_id,creative&limit=250&access_token={$token}";
            $adsResponse = file_get_contents($adsUrl);
            $adsData = json_decode($adsResponse, true);

            $campaignsForPage = [];
            if (isset($adsData['data'])) {
                foreach ($adsData['data'] as $ad) {
                    if (isset($ad['creative']['id'])) {
                        $creativeId = $ad['creative']['id'];
                        $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$token}";
                        $creativeResponse = file_get_contents($creativeUrl);
                        $creativeData = json_decode($creativeResponse, true);

                        if (isset($creativeData['object_story_spec']['page_id']) && 
                            $creativeData['object_story_spec']['page_id'] == $testPageId) {
                            $campaignsForPage[$ad['campaign_id']] = true;
                        }
                    }
                }
            }

            // Mostrar campañas filtradas
            $this->info("\n📊 Campañas filtradas para la página {$testPageId}:");
            $filteredCount = 0;
            foreach ($campaignsData['data'] as $campaign) {
                if ($campaign['status'] == 'ACTIVE' && isset($campaignsForPage[$campaign['id']])) {
                    $filteredCount++;
                    $this->info("  ✅ {$campaign['name']} (ID: {$campaign['id']})");
                }
            }

            // 5. Resumen de la jerarquía
            $this->info("\n📊 Resumen de la nueva jerarquía:");
            $this->info("  1. Token de Hazabeth Romero ✅");
            $this->info("  2. Cuentas publicitarias: " . count($adAccountsData['data']) . " cuentas ✅");
            $this->info("  3. Páginas disponibles: " . count($pagesData['data']) . " páginas ✅");
            $this->info("  4. Campañas en cuenta {$testAdAccountId}: " . count($campaignsData['data']) . " campañas ✅");
            $this->info("  5. Campañas filtradas por página {$testPageId}: {$filteredCount} campañas ✅");

            $this->info("\n🔄 Flujo completo del formulario:");
            $this->info("  1. Usuario ve todas las cuentas publicitarias disponibles");
            $this->info("  2. Usuario selecciona una cuenta publicitaria");
            $this->info("  3. Usuario ve todas las páginas disponibles");
            $this->info("  4. Usuario selecciona una página");
            $this->info("  5. Sistema filtra campañas por página seleccionada");
            $this->info("  6. Usuario selecciona campañas");
            $this->info("  7. Sistema filtra anuncios por campañas seleccionadas");

            $this->info("\n✅ Prueba de nueva jerarquía completada exitosamente");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
