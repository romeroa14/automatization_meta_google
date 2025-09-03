<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestMetaApiDetection extends Command
{
    protected $signature = 'test:meta-api-detection {--token= : Access token de Meta} {--account-id= : ID de cuenta publicitaria}';
    protected $description = 'Probar la detección de campañas desde la API de Meta';

    public function handle()
    {
        $this->info('🔍 PROBANDO DETECCIÓN DE CAMPAÑAS DESDE META API');
        $this->newLine();

        $accessToken = $this->option('token');
        $adAccountId = $this->option('account-id');

        if (!$accessToken) {
            $accessToken = $this->ask('Ingresa tu Access Token de Meta (EAA...)');
        }

        if (!$adAccountId) {
            $adAccountId = $this->ask('Ingresa el ID de tu cuenta publicitaria (sin act_)');
        }

        if (!$accessToken || !$adAccountId) {
            $this->error('❌ Access Token y Account ID son requeridos');
            return 1;
        }

        $this->info("🔑 Token: " . substr($accessToken, 0, 20) . "...");
        $this->info("🏢 Account ID: act_{$adAccountId}");
        $this->newLine();

        try {
            // 1. Probar obtención de campañas
            $this->info('📊 1. OBTENIENDO CAMPAÑAS...');
            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time,objective,budget_remaining,budget&limit=250&access_token={$accessToken}";
            
            $this->line("URL: {$campaignsUrl}");
            $response = file_get_contents($campaignsUrl);
            $campaignsData = json_decode($response, true);

            if (!isset($campaignsData['data'])) {
                $this->error('❌ Error obteniendo campañas: ' . json_encode($campaignsData));
                return 1;
            }

            $totalCampaigns = count($campaignsData['data']);
            $activeCampaigns = [];
            $inactiveCampaigns = [];

            foreach ($campaignsData['data'] as $campaign) {
                if ($campaign['status'] == 'ACTIVE') {
                    $activeCampaigns[] = $campaign;
                } else {
                    $inactiveCampaigns[] = $campaign;
                }
            }

            $this->info("✅ Total de campañas: {$totalCampaigns}");
            $this->info("🟢 Campañas activas: " . count($activeCampaigns));
            $this->info("🔴 Campañas inactivas: " . count($inactiveCampaigns));
            $this->newLine();

            // 2. Mostrar campañas activas
            if (!empty($activeCampaigns)) {
                $this->info('🟢 CAMPAÑAS ACTIVAS DETECTADAS:');
                $this->newLine();
                
                $headers = ['ID', 'Nombre', 'Presupuesto Diario', 'Presupuesto Total', 'Budget Remaining', 'Budget', 'Objetivo'];
                $rows = [];

                foreach ($activeCampaigns as $campaign) {
                    // Presupuesto diario
                    $dailyBudget = isset($campaign['daily_budget']) ? $campaign['daily_budget'] : null;
                    $dailyBudgetText = 'N/A';
                    if ($dailyBudget !== null && is_numeric($dailyBudget)) {
                        if ($dailyBudget > 1000) {
                            $dailyBudget = $dailyBudget / 100;
                        }
                        $dailyBudgetText = '$' . number_format($dailyBudget, 2);
                    }

                    // Presupuesto total
                    $lifetimeBudget = isset($campaign['lifetime_budget']) ? $campaign['lifetime_budget'] : null;
                    $lifetimeBudgetText = 'N/A';
                    if ($lifetimeBudget !== null && is_numeric($lifetimeBudget)) {
                        if ($lifetimeBudget > 1000) {
                            $lifetimeBudget = $lifetimeBudget / 100;
                        }
                        $lifetimeBudgetText = '$' . number_format($lifetimeBudget, 2);
                    }

                    // Budget remaining
                    $budgetRemaining = isset($campaign['budget_remaining']) ? $campaign['budget_remaining'] : null;
                    $budgetRemainingText = 'N/A';
                    if ($budgetRemaining !== null && is_numeric($budgetRemaining)) {
                        if ($budgetRemaining > 1000) {
                            $budgetRemaining = $budgetRemaining / 100;
                        }
                        $budgetRemainingText = '$' . number_format($budgetRemaining, 2);
                    }

                    // Budget
                    $budget = isset($campaign['budget']) ? $campaign['budget'] : null;
                    $budgetText = 'N/A';
                    if ($budget !== null && is_numeric($budget)) {
                        if ($budget > 1000) {
                            $budget = $budget / 100;
                        }
                        $budgetText = '$' . number_format($budget, 2);
                    }

                    $rows[] = [
                        $campaign['id'],
                        substr($campaign['name'], 0, 30) . (strlen($campaign['name']) > 30 ? '...' : ''),
                        $dailyBudgetText,
                        $lifetimeBudgetText,
                        $budgetRemainingText,
                        $budgetText,
                        $campaign['objective'] ?? 'N/A'
                    ];
                }

                $this->table($headers, $rows);
                $this->newLine();
            }

            // 3. Probar obtención de anuncios
            $this->info('📱 2. OBTENIENDO ANUNCIOS...');
            $adsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads?fields=id,name,campaign_id,creative,status&limit=250&access_token={$accessToken}";
            
            $adsResponse = file_get_contents($adsUrl);
            $adsData = json_decode($adsResponse, true);

            if (!isset($adsData['data'])) {
                $this->error('❌ Error obteniendo anuncios: ' . json_encode($adsData));
                return 1;
            }

            $totalAds = count($adsData['data']);
            $activeAds = [];
            $adsWithCreative = 0;

            foreach ($adsData['data'] as $ad) {
                if ($ad['status'] == 'ACTIVE') {
                    $activeAds[] = $ad;
                }
                if (isset($ad['creative']['id'])) {
                    $adsWithCreative++;
                }
            }

            $this->info("✅ Total de anuncios: {$totalAds}");
            $this->info("🟢 Anuncios activos: " . count($activeAds));
            $this->info("🎨 Anuncios con creative: {$adsWithCreative}");
            $this->newLine();

            // 4. Probar obtención de páginas
            $this->info('📄 3. OBTENIENDO PÁGINAS...');
            $pagesUrl = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$accessToken}";
            
            $pagesResponse = file_get_contents($pagesUrl);
            $pagesData = json_decode($pagesResponse, true);

            if (isset($pagesData['data'])) {
                $this->info("✅ Fan Pages encontradas: " . count($pagesData['data']));
                foreach ($pagesData['data'] as $page) {
                    $this->line("   📱 {$page['name']} (ID: {$page['id']}) - {$page['category']}");
                }
            } else {
                $this->warn("⚠️ No se pudieron obtener fan pages");
            }
            $this->newLine();

            // 5. Probar obtención de cuentas de Instagram
            $this->info('📸 4. OBTENIENDO CUENTAS DE INSTAGRAM...');
            $instagramUrl = "https://graph.facebook.com/v18.0/me/accounts?type=instagram&limit=250&access_token={$accessToken}";
            
            $instagramResponse = @file_get_contents($instagramUrl);
            if ($instagramResponse !== false) {
                $instagramData = json_decode($instagramResponse, true);
                if (isset($instagramData['data'])) {
                    $this->info("✅ Cuentas de Instagram encontradas: " . count($instagramData['data']));
                    foreach ($instagramData['data'] as $instagram) {
                        $this->line("   📸 {$instagram['name']} (ID: {$instagram['id']})");
                    }
                } else {
                    $this->warn("⚠️ No se encontraron cuentas de Instagram");
                }
            } else {
                $this->warn("⚠️ No se pudieron obtener cuentas de Instagram");
            }
            $this->newLine();

            // 6. Resumen final
            $this->info('🎯 RESUMEN FINAL:');
            $this->line("   • Total de campañas: {$totalCampaigns}");
            $this->line("   • Campañas activas: " . count($activeCampaigns));
            $this->line("   • Total de anuncios: {$totalAds}");
            $this->line("   • Anuncios activos: " . count($activeAds));
            $this->line("   • Anuncios con creative: {$adsWithCreative}");
            
            if (isset($pagesData['data'])) {
                $this->line("   • Fan Pages: " . count($pagesData['data']));
            }
            
            if (isset($instagramData['data'])) {
                $this->line("   • Cuentas Instagram: " . count($instagramData['data']));
            }

            $this->newLine();
            $this->info('✅ PRUEBA COMPLETADA EXITOSAMENTE');
            
            if (count($activeCampaigns) > 0) {
                $this->info('🎉 ¡Se detectaron campañas activas! El sistema debería funcionar correctamente.');
            } else {
                $this->warn('⚠️ No se detectaron campañas activas. Verifica que tengas campañas en estado ACTIVE.');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error durante la prueba: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
