<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActiveCampaign;

class CreateTestCampaigns extends Command
{
    protected $signature = 'create:test-campaigns';
    protected $description = 'Crear campañas de prueba para demostrar el funcionamiento';

    public function handle()
    {
        $this->info('🧪 Creando campañas de prueba...');
        
        // Limpiar campañas existentes
        ActiveCampaign::query()->delete();
        
        // Crear campañas de prueba con datos reales
        $testCampaigns = [
            [
                'meta_campaign_id' => '120235594177550064',
                'meta_adset_id' => '120235594177550064',
                'meta_ad_id' => '120235594177530064',
                'meta_campaign_name' => 'Od.yulianadossantos | 07/09/2025 - 13/09/2025 - Copia',
                'meta_adset_name' => 'AdSet Od.yulianadossantos',
                'meta_ad_name' => 'Ad Od.yulianadossantos',
                'campaign_daily_budget' => 2.00,
                'campaign_total_budget' => 12.00,
                'adset_daily_budget' => 2.00,
                'adset_lifetime_budget' => 12.00,
                'campaign_status' => 'ACTIVE',
                'adset_status' => 'ACTIVE',
                'ad_status' => 'ACTIVE',
                'campaign_objective' => 'REACH',
                'facebook_account_id' => 1,
                'ad_account_id' => '1124273537782021',
                'campaign_start_time' => now()->subDays(1),
                'campaign_stop_time' => now()->addDays(5),
                'adset_start_time' => now()->subDays(1),
                'adset_stop_time' => now()->addDays(5),
                'campaign_data' => [
                    'id' => '120235594177550064',
                    'name' => 'Od.yulianadossantos | 07/09/2025 - 13/09/2025 - Copia',
                    'status' => 'ACTIVE',
                    'daily_budget' => 200, // En centavos
                    'lifetime_budget' => 1200, // En centavos
                    'start_time' => now()->subDays(1)->toISOString(),
                    'stop_time' => now()->addDays(5)->toISOString(),
                    'objective' => 'REACH',
                    'amount_spent' => 450 // En centavos
                ],
                'adset_data' => [
                    'id' => '120235594177550064',
                    'name' => 'AdSet Od.yulianadossantos',
                    'status' => 'ACTIVE',
                    'daily_budget' => 200,
                    'lifetime_budget' => 1200,
                    'start_time' => now()->subDays(1)->toISOString(),
                    'stop_time' => now()->addDays(5)->toISOString(),
                    'amount_spent' => 450
                ],
                'ad_data' => [
                    'id' => '120235594177530064',
                    'name' => 'Ad Od.yulianadossantos',
                    'status' => 'ACTIVE',
                    'creative' => [
                        'id' => '120235594177530064',
                        'actor_id' => '123456789',
                        'object_story_spec' => [
                            'page_id' => '123456789'
                        ]
                    ]
                ]
            ],
            [
                'meta_campaign_id' => '120235560130740064',
                'meta_adset_id' => '120235560130740064',
                'meta_ad_id' => '120235560130790064',
                'meta_campaign_name' => 'Impresionamos | 07/09/2025 -06/10/2025',
                'meta_adset_name' => 'AdSet Impresionamos',
                'meta_ad_name' => 'Ad Impresionamos',
                'campaign_daily_budget' => 2.00,
                'campaign_total_budget' => 60.00,
                'adset_daily_budget' => 2.00,
                'adset_lifetime_budget' => 60.00,
                'campaign_status' => 'ACTIVE',
                'adset_status' => 'ACTIVE',
                'ad_status' => 'ACTIVE',
                'campaign_objective' => 'TRAFFIC',
                'facebook_account_id' => 1,
                'ad_account_id' => '1124273537782021',
                'campaign_start_time' => now()->subDays(1),
                'campaign_stop_time' => now()->addDays(30),
                'adset_start_time' => now()->subDays(1),
                'adset_stop_time' => now()->addDays(30),
                'campaign_data' => [
                    'id' => '120235560130740064',
                    'name' => 'Impresionamos | 07/09/2025 -06/10/2025',
                    'status' => 'ACTIVE',
                    'daily_budget' => 200,
                    'lifetime_budget' => 6000,
                    'start_time' => now()->subDays(1)->toISOString(),
                    'stop_time' => now()->addDays(30)->toISOString(),
                    'objective' => 'TRAFFIC',
                    'amount_spent' => 1200
                ],
                'adset_data' => [
                    'id' => '120235560130740064',
                    'name' => 'AdSet Impresionamos',
                    'status' => 'ACTIVE',
                    'daily_budget' => 200,
                    'lifetime_budget' => 6000,
                    'start_time' => now()->subDays(1)->toISOString(),
                    'stop_time' => now()->addDays(30)->toISOString(),
                    'amount_spent' => 1200
                ],
                'ad_data' => [
                    'id' => '120235560130790064',
                    'name' => 'Ad Impresionamos',
                    'status' => 'ACTIVE',
                    'creative' => [
                        'id' => '120235560130790064',
                        'actor_id' => '987654321',
                        'object_story_spec' => [
                            'page_id' => '987654321'
                        ]
                    ]
                ]
            ]
        ];
        
        foreach ($testCampaigns as $campaignData) {
            $campaign = new ActiveCampaign();
            $campaign->fill($campaignData);
            $campaign->save();
            
            $this->line("✅ Creada: {$campaign->meta_campaign_name}");
        }
        
        $this->info("🎉 Se crearon " . count($testCampaigns) . " campañas de prueba");
        
        // Mostrar resumen
        foreach (ActiveCampaign::all() as $campaign) {
            $this->line("📋 {$campaign->meta_campaign_name}");
            $this->line("   💰 Presupuesto diario: $" . number_format($campaign->campaign_daily_budget ?? $campaign->adset_daily_budget ?? 0, 2));
            $this->line("   📅 Inicio: " . ($campaign->campaign_start_time?->format('Y-m-d') ?? 'N/A'));
            $this->line("   📅 Final: " . ($campaign->campaign_stop_time?->format('Y-m-d') ?? 'N/A'));
        }
    }
}
