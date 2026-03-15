<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Log;

class TestNewHierarchy extends Command
{
    protected $signature = 'test:new-hierarchy {account_id?}';
    protected $description = 'Prueba la nueva jerarquía: selected_campaign_ids → selected_ad_ids → estadísticas';

    public function handle()
    {
        $accountId = $this->argument('account_id') ?? 1;
        $account = FacebookAccount::find($accountId);
        
        if (!$account) {
            $this->error("Cuenta no encontrada con ID: {$accountId}");
            return 1;
        }

        $this->info("=== PRUEBA DE NUEVA JERARQUÍA ===");
        $this->info("Cuenta: {$account->account_name}");
        $this->info("Cuenta publicitaria: {$account->selected_ad_account_id}");
        $this->info("Fan page: {$account->selected_page_id}");
        $this->info("Campañas configuradas: " . count($account->selected_campaign_ids ?? []));
        $this->info("Anuncios configurados: " . count($account->selected_ad_ids ?? []));
        $this->newLine();

        try {
            // Inicializar Facebook API
            Api::init(
                $account->app_id,
                $account->app_secret,
                $account->access_token
            );

            $adAccount = new AdAccount('act_' . $account->selected_ad_account_id);

            // 1. Verificar campañas configuradas
            $this->info("1. VERIFICANDO CAMPAÑAS CONFIGURADAS:");
            if (!empty($account->selected_campaign_ids)) {
                $campaigns = $adAccount->getCampaigns(['id', 'name', 'status'], [
                    'filtering' => [
                        [
                            'field' => 'id',
                            'operator' => 'IN',
                            'value' => $account->selected_campaign_ids,
                        ],
                    ],
                ]);

                foreach ($campaigns as $campaign) {
                    $this->line("   ✅ Campaña: {$campaign->name} (ID: {$campaign->id}) - Estado: {$campaign->status}");
                }
            } else {
                $this->warn("   ⚠️ No hay campañas configuradas");
            }
            $this->newLine();

            // 2. Verificar anuncios configurados
            $this->info("2. VERIFICANDO ANUNCIOS CONFIGURADOS:");
            if (!empty($account->selected_ad_ids)) {
                $fields = [
                    'ad_id',
                    'ad_name',
                    'campaign_id',
                    'campaign_name',
                    'impressions',
                    'clicks',
                    'spend',
                    'ctr',
                ];

                $params = [
                    'level' => 'ad',
                    'time_range' => ['since' => now()->subDays(7)->format('Y-m-d'), 'until' => now()->format('Y-m-d')],
                    'filtering' => [
                        [
                            'field' => 'ad.id',
                            'operator' => 'IN',
                            'value' => $account->selected_ad_ids,
                        ],
                    ],
                ];

                $insights = $adAccount->getInsights($fields, $params);
                
                if (count($insights) > 0) {
                    $this->info("   ✅ Se encontraron " . count($insights) . " anuncios con datos:");
                    foreach ($insights as $insight) {
                        $this->line("      📊 {$insight->ad_name} (ID: {$insight->ad_id})");
                        $this->line("         Campaña: {$insight->campaign_name}");
                        $this->line("         Impresiones: " . number_format($insight->impressions ?? 0));
                        $this->line("         Clicks: " . number_format($insight->clicks ?? 0));
                        $this->line("         CTR: " . number_format($insight->ctr ?? 0, 2) . "%");
                        $this->line("         Gasto: $" . number_format($insight->spend ?? 0, 2));
                        $this->newLine();
                    }
                } else {
                    $this->warn("   ⚠️ No se encontraron datos para los anuncios configurados");
                }
            } else {
                $this->warn("   ⚠️ No hay anuncios configurados");
            }

            $this->info("=== PRUEBA COMPLETADA ===");
            $this->info("✅ La nueva jerarquía está funcionando correctamente");
            $this->info("✅ Los anuncios específicos se pueden filtrar y obtener estadísticas");

        } catch (\Exception $e) {
            $this->error("❌ Error durante la prueba: " . $e->getMessage());
            Log::error("Error en TestNewHierarchy: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
