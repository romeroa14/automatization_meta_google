<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignReconciliationService;
use App\Models\FacebookAccount;
use App\Models\CampaignReconciliation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SyncMetaCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meta:sync-campaigns {--account-id= : ID específico de cuenta de Facebook} {--force : Forzar sincronización completa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar campañas de Meta Ads con el sistema de conciliación de ADMETRICAS.COM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 **SINCRONIZACIÓN AUTOMÁTICA CON META ADS**');
        $this->info('Sistema: ADMETRICAS.COM');
        $this->newLine();

        try {
            $accountId = $this->option('account-id');
            $force = $this->option('force');

            if ($accountId) {
                $this->syncSpecificAccount($accountId, $force);
            } else {
                $this->syncAllAccounts($force);
            }

            $this->newLine();
            $this->info('✅ **SINCRONIZACIÓN COMPLETADA EXITOSAMENTE!**');

        } catch (\Exception $e) {
            $this->error('❌ Error en la sincronización: ' . $e->getMessage());
            Log::error('Error en sincronización de Meta: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Sincronizar una cuenta específica
     */
    private function syncSpecificAccount(string $accountId, bool $force): void
    {
        $this->info("🎯 **SINCRONIZANDO CUENTA ESPECÍFICA: {$accountId}**");
        
        $account = FacebookAccount::find($accountId);
        if (!$account) {
            $this->error("❌ No se encontró la cuenta con ID: {$accountId}");
            return;
        }

        $this->info("📱 Cuenta: {$account->account_name}");
        $this->info("🔑 Estado: " . ($account->is_active ? 'Activa' : 'Inactiva'));
        
        if (!$account->is_active) {
            $this->warn("⚠️  La cuenta está inactiva. No se puede sincronizar.");
            return;
        }

        $this->syncAccountCampaigns($account, $force);
    }

    /**
     * Sincronizar todas las cuentas activas
     */
    private function syncAllAccounts(bool $force): void
    {
        $this->info('🌐 **SINCRONIZANDO TODAS LAS CUENTAS ACTIVAS**');
        
        $accounts = FacebookAccount::where('is_active', true)->get();
        
        if ($accounts->isEmpty()) {
            $this->warn("⚠️  No hay cuentas de Facebook activas para sincronizar.");
            return;
        }

        $this->info("📊 Total de cuentas activas: {$accounts->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($accounts->count());
        $bar->start();

        foreach ($accounts as $account) {
            $this->syncAccountCampaigns($account, $force);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Sincronizar campañas de una cuenta específica
     */
    private function syncAccountCampaigns(FacebookAccount $account, bool $force): void
    {
        try {
            $this->line("🔄 Sincronizando cuenta: {$account->account_name}");

            // Obtener campañas desde Meta API
            $campaigns = $this->getMetaCampaigns($account);
            
            if (empty($campaigns)) {
                $this->line("   ⚠️  No se encontraron campañas activas");
                return;
            }

            $this->line("   📊 Campañas encontradas: " . count($campaigns));

            // Procesar cada campaña
            $processed = 0;
            $reconciled = 0;
            $errors = 0;

            foreach ($campaigns as $campaign) {
                try {
                    $result = $this->processCampaign($account, $campaign, $force);
                    
                    if ($result['success']) {
                        $processed++;
                        if ($result['reconciled']) {
                            $reconciled++;
                        }
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->line("   ❌ Error procesando campaña {$campaign['name']}: " . $e->getMessage());
                }
            }

            $this->line("   ✅ Procesadas: {$processed} | Conciliadas: {$reconciled} | Errores: {$errors}");

        } catch (\Exception $e) {
            $this->error("   ❌ Error sincronizando cuenta {$account->account_name}: " . $e->getMessage());
            Log::error("Error sincronizando cuenta {$account->account_name}: " . $e->getMessage());
        }
    }

    /**
     * Obtener campañas desde Meta API
     */
    private function getMetaCampaigns(FacebookAccount $account): array
    {
        try {
            // Verificar que la cuenta tenga los datos necesarios
            if (!$account->access_token || !$account->selected_ad_account_id) {
                $this->line("   ⚠️  Cuenta sin token o cuenta publicitaria seleccionada");
                return [];
            }

            $accessToken = $account->access_token;
            $adAccountId = $account->selected_ad_account_id;

            // Obtener campañas activas
            $url = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns";
            $params = [
                'fields' => 'id,name,status,daily_budget,lifetime_budget,start_time,stop_time,created_time',
                'status' => 'ACTIVE',
                'limit' => 250,
                'access_token' => $accessToken
            ];

            $response = Http::get($url, $params);
            
            if (!$response->successful()) {
                $this->line("   ❌ Error API Meta: " . $response->body());
                return [];
            }

            $data = $response->json();
            
            if (!isset($data['data'])) {
                $this->line("   ⚠️  No se encontraron datos de campañas");
                return [];
            }

            return $data['data'];

        } catch (\Exception $e) {
            $this->line("   ❌ Error obteniendo campañas: " . $e->getMessage());
            Log::error("Error obteniendo campañas de Meta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Procesar una campaña individual
     */
    private function processCampaign(FacebookAccount $account, array $campaign, bool $force): array
    {
        try {
            // Verificar si ya existe una conciliación para esta campaña
            $existingReconciliation = CampaignReconciliation::where('meta_campaign_id', $campaign['id'])->first();
            
            if ($existingReconciliation && !$force) {
                return [
                    'success' => true,
                    'reconciled' => false,
                    'details' => "Campaña ya conciliada"
                ];
            }

            // Extraer información de la campaña
            $campaignInfo = $this->extractCampaignInfo($campaign);
            
            // Crear o actualizar la conciliación
            if ($existingReconciliation) {
                $this->updateReconciliation($existingReconciliation, $campaignInfo);
                $reconciliation = $existingReconciliation;
            } else {
                $reconciliation = $this->createReconciliation($account, $campaign, $campaignInfo);
            }

            // Intentar detectar el plan de publicidad
            $detectedPlan = $this->detectAdvertisingPlan($campaignInfo);
            
            if ($detectedPlan) {
                $this->updateReconciliationWithPlan($reconciliation, $detectedPlan);
            }

            return [
                'success' => true,
                'reconciled' => true,
                'details' => "Campaña procesada exitosamente"
            ];

        } catch (\Exception $e) {
            Log::error("Error procesando campaña {$campaign['name']}: " . $e->getMessage());
            return [
                'success' => false,
                'reconciled' => false,
                'details' => "Error: " . $e->getMessage()
            ];
        }
    }

    /**
     * Extraer información relevante de la campaña de Meta
     */
    private function extractCampaignInfo(array $campaign): array
    {
        return [
            'daily_budget' => $this->extractDailyBudget($campaign),
            'duration_days' => $this->estimateDurationDays($campaign),
            'total_budget' => $this->extractTotalBudget($campaign),
            'client_name' => $this->extractClientName($campaign),
            'start_date' => $this->extractStartDate($campaign),
            'end_date' => $this->extractEndDate($campaign),
        ];
    }

    /**
     * Extraer presupuesto diario de la campaña
     */
    private function extractDailyBudget(array $campaign): float
    {
        $dailyBudget = $campaign['daily_budget'] ?? 
                      $campaign['budget_remaining'] ?? 
                      $campaign['budget'] ?? 
                      0;

        // Convertir de centavos a dólares si es necesario
        if ($dailyBudget > 1000) {
            $dailyBudget = $dailyBudget / 100;
        }

        return (float) $dailyBudget;
    }

    /**
     * Estimar duración en días de la campaña
     */
    private function estimateDurationDays(array $campaign): int
    {
        $startDate = $this->extractStartDate($campaign);
        $endDate = $this->extractEndDate($campaign);
        
        if ($startDate && $endDate) {
            return \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        }
        
        // Si no hay fechas, estimar basado en el presupuesto total
        $totalBudget = $this->extractTotalBudget($campaign);
        $dailyBudget = $this->extractDailyBudget($campaign);
        
        if ($dailyBudget > 0) {
            return (int) ceil($totalBudget / $dailyBudget);
        }
        
        return 7; // Duración por defecto
    }

    /**
     * Extraer presupuesto total de la campaña
     */
    private function extractTotalBudget(array $campaign): float
    {
        $totalBudget = $campaign['lifetime_budget'] ?? 
                      $campaign['budget'] ?? 
                      $campaign['budget_remaining'] ?? 
                      0;

        // Convertir de centavos a dólares si es necesario
        if ($totalBudget > 1000) {
            $totalBudget = $totalBudget / 100;
        }

        return (float) $totalBudget;
    }

    /**
     * Extraer nombre del cliente de la campaña
     */
    private function extractClientName(array $campaign): string
    {
        $campaignName = $campaign['name'] ?? '';
        
        // Buscar patrones comunes en nombres de campaña
        if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/', $campaignName, $matches)) {
            return $matches[1];
        }
        
        return 'Cliente Sin Identificar';
    }

    /**
     * Extraer fecha de inicio de la campaña
     */
    private function extractStartDate(array $campaign): ?string
    {
        return $campaign['start_time'] ?? 
               $campaign['created_time'] ?? 
               $campaign['start_date'] ?? 
               null;
    }

    /**
     * Extraer fecha de fin de la campaña
     */
    private function extractEndDate(array $campaign): ?string
    {
        return $campaign['stop_time'] ?? 
               $campaign['end_time'] ?? 
               $campaign['end_date'] ?? 
               null;
    }

    /**
     * Detectar automáticamente el plan de publicidad
     */
    private function detectAdvertisingPlan(array $campaignInfo): ?\App\Models\AdvertisingPlan
    {
        $dailyBudget = $campaignInfo['daily_budget'];
        $durationDays = $campaignInfo['duration_days'];
        
        // Buscar planes que coincidan exactamente
        $matchingPlan = \App\Models\AdvertisingPlan::active()
            ->where('daily_budget', $dailyBudget)
            ->where('duration_days', $durationDays)
            ->first();
        
        if ($matchingPlan) {
            return $matchingPlan;
        }
        
        // Si no hay coincidencia exacta, buscar el más cercano
        $closestPlan = \App\Models\AdvertisingPlan::active()
            ->get()
            ->sortBy(function ($plan) use ($dailyBudget, $durationDays) {
                $budgetDiff = abs($plan->daily_budget - $dailyBudget);
                $durationDiff = abs($plan->duration_days - $durationDays);
                return $budgetDiff + $durationDiff;
            })
            ->first();
        
        // Solo usar si la diferencia es razonable
        if ($closestPlan) {
            $budgetDiff = abs($closestPlan->daily_budget - $dailyBudget);
            $durationDiff = abs($closestPlan->duration_days - $durationDays);
            
            if ($budgetDiff <= 1.00 && $durationDiff <= 2) {
                return $closestPlan;
            }
        }
        
        return null;
    }

    /**
     * Crear nueva conciliación
     */
    private function createReconciliation(FacebookAccount $account, array $campaign, array $campaignInfo): CampaignReconciliation
    {
        return CampaignReconciliation::create([
            'facebook_account_id' => $account->id,
            'meta_campaign_id' => $campaign['id'],
            'meta_campaign_name' => $campaign['name'],
            'client_name' => $campaignInfo['client_name'],
            'client_type' => 'fanpage',
            'daily_budget' => $campaignInfo['daily_budget'],
            'duration_days' => $campaignInfo['duration_days'],
            'total_budget' => $campaignInfo['total_budget'],
            'actual_spend' => 0,
            'remaining_budget' => $campaignInfo['total_budget'],
            'status' => 'pending',
            'campaign_start_date' => $campaignInfo['start_date'],
            'campaign_end_date' => $campaignInfo['end_date'],
            'meta_data' => $campaign,
            'notes' => 'Creado automáticamente por sincronización'
        ]);
    }

    /**
     * Actualizar conciliación existente
     */
    private function updateReconciliation(CampaignReconciliation $reconciliation, array $campaignInfo): void
    {
        $reconciliation->update([
            'daily_budget' => $campaignInfo['daily_budget'],
            'duration_days' => $campaignInfo['duration_days'],
            'total_budget' => $campaignInfo['total_budget'],
            'remaining_budget' => max(0, $campaignInfo['total_budget'] - $reconciliation->actual_spend),
            'campaign_start_date' => $campaignInfo['start_date'],
            'campaign_end_date' => $campaignInfo['end_date'],
            'meta_data' => array_merge($reconciliation->meta_data ?? [], ['last_sync' => now()]),
            'notes' => 'Actualizado automáticamente por sincronización'
        ]);
    }

    /**
     * Actualizar conciliación con plan detectado
     */
    private function updateReconciliationWithPlan(CampaignReconciliation $reconciliation, \App\Models\AdvertisingPlan $plan): void
    {
        $reconciliation->update([
            'advertising_plan_id' => $plan->id,
            'client_price' => $plan->client_price,
            'profit_margin' => $plan->profit_margin,
            'notes' => "Plan detectado automáticamente: {$plan->plan_name}"
        ]);
    }
}
