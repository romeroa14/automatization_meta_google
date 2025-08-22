<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;

class TestFormFiltering extends Command
{
    protected $signature = 'facebook:test-form-filtering {account_id}';
    protected $description = 'Probar el filtrado completo del formulario';

    public function handle()
    {
        $accountId = $this->argument('account_id');

        $account = FacebookAccount::find($accountId);
        if (!$account) {
            $this->error("No se encontró la cuenta de Facebook con ID: {$accountId}");
            return 1;
        }

        $this->info("🔍 Probando filtrado completo del formulario...");
        $this->info("📱 Usando cuenta: {$account->account_name}");
        $this->info("💰 Cuenta publicitaria: {$account->account_id}");

        try {
            $token = $account->access_token;
            $adAccountId = $account->account_id;

            // 1. Obtener páginas disponibles
            $this->info("\n📄 Obteniendo páginas disponibles...");
            $pagesUrl = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$token}";
            $pagesResponse = file_get_contents($pagesUrl);
            $pagesData = json_decode($pagesResponse, true);
            
            $this->info("Total páginas encontradas: " . count($pagesData['data']));

            // 2. Mostrar algunas páginas como ejemplo
            $this->info("\n📄 Ejemplos de páginas disponibles:");
            for ($i = 0; $i < min(5, count($pagesData['data'])); $i++) {
                $page = $pagesData['data'][$i];
                $this->info("  - {$page['name']} (ID: {$page['id']}) - {$page['category']}");
            }

            // 3. Probar filtrado con una página específica
            $testPageId = '1531861783507690'; // Moda Brands Shop
            $this->info("\n🎯 Probando filtrado con página: Moda Brands Shop ({$testPageId})");

            // Obtener campañas de la cuenta
            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status&limit=250&access_token={$token}";
            $campaignsResponse = file_get_contents($campaignsUrl);
            $campaignsData = json_decode($campaignsResponse, true);

            // Obtener anuncios y filtrar por página
            $adsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads?fields=id,name,campaign_id,creative&limit=250&access_token={$token}";
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
            $this->info("\n📊 Campañas filtradas para Moda Brands Shop:");
            $filteredCount = 0;
            foreach ($campaignsData['data'] as $campaign) {
                if ($campaign['status'] == 'ACTIVE' && isset($campaignsForPage[$campaign['id']])) {
                    $filteredCount++;
                    $this->info("  ✅ {$campaign['name']} (ID: {$campaign['id']})");
                }
            }

            $this->info("\n📊 Resumen del filtrado:");
            $this->info("  Total campañas en la cuenta: " . count($campaignsData['data']));
            $this->info("  Campañas activas: " . count(array_filter($campaignsData['data'], fn($c) => $c['status'] == 'ACTIVE')));
            $this->info("  Campañas filtradas por página: {$filteredCount}");

            // 4. Simular el flujo completo del formulario
            $this->info("\n🔄 Simulando flujo completo del formulario:");
            $this->info("  1. Usuario selecciona cuenta publicitaria: {$adAccountId}");
            $this->info("  2. Usuario selecciona página: Moda Brands Shop ({$testPageId})");
            $this->info("  3. Sistema filtra campañas: {$filteredCount} campañas disponibles");
            $this->info("  4. Usuario puede seleccionar campañas específicas");
            $this->info("  5. Sistema filtra anuncios por campañas seleccionadas");

            $this->info("\n✅ Prueba completada exitosamente");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
