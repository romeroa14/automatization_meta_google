<?php

namespace App\Services;

use App\Models\AdvertisingPlan;
use App\Models\CampaignReconciliation;
use App\Models\AccountingTransaction;
use App\Models\FacebookAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CampaignReconciliationService
{
    /**
     * Detectar y conciliar campañas automáticamente
     */
    public function detectAndReconcileCampaigns(): array
    {
        $results = [
            'detected' => 0,
            'reconciled' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            // Obtener todas las cuentas de Facebook activas
            $facebookAccounts = FacebookAccount::where('is_active', true)->get();
            
            foreach ($facebookAccounts as $account) {
                $accountResults = $this->processAccountCampaigns($account);
                
                $results['detected'] += $accountResults['detected'];
                $results['reconciled'] += $accountResults['reconciled'];
                $results['errors'] = array_merge($results['errors'], $accountResults['errors']);
                $results['details'] = array_merge($results['details'], $accountResults['details']);
            }
            
        } catch (\Exception $e) {
            Log::error('Error en detección automática de campañas: ' . $e->getMessage());
            $results['errors'][] = 'Error general: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Procesar campañas de una cuenta específica
     */
    private function processAccountCampaigns(FacebookAccount $account): array
    {
        $results = [
            'detected' => 0,
            'reconciled' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            // Obtener campañas activas desde Meta API
            $campaigns = $this->getMetaCampaigns($account);
            
            foreach ($campaigns as $campaign) {
                $campaignResult = $this->processSingleCampaign($account, $campaign);
                
                if ($campaignResult['success']) {
                    $results['detected']++;
                    if ($campaignResult['reconciled']) {
                        $results['reconciled']++;
                    }
                    $results['details'][] = $campaignResult['details'];
                } else {
                    $results['errors'][] = $campaignResult['error'];
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Error procesando cuenta {$account->account_name}: " . $e->getMessage());
            $results['errors'][] = "Error en cuenta {$account->account_name}: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Procesar una campaña individual
     */
    private function processSingleCampaign(FacebookAccount $account, array $campaign): array
    {
        try {
            // Verificar si ya existe una conciliación para esta campaña
            $existingReconciliation = CampaignReconciliation::where('meta_campaign_id', $campaign['id'])->first();
            
            if ($existingReconciliation) {
                return [
                    'success' => true,
                    'reconciled' => false,
                    'details' => "Campaña {$campaign['name']} ya conciliada",
                    'error' => null
                ];
            }

            // Extraer información de la campaña
            $campaignInfo = $this->extractCampaignInfo($campaign);
            
            // Intentar detectar el plan de publicidad
            $detectedPlan = $this->detectAdvertisingPlan($campaignInfo);
            
            // Crear la conciliación
            $reconciliation = $this->createReconciliation($account, $campaign, $campaignInfo, $detectedPlan);
            
            // Crear transacciones contables si se detectó un plan
            if ($detectedPlan) {
                $this->createAccountingTransactions($reconciliation, $detectedPlan);
            }

            return [
                'success' => true,
                'reconciled' => true,
                'details' => "Campaña {$campaign['name']} conciliada exitosamente",
                'error' => null
            ];

        } catch (\Exception $e) {
            Log::error("Error procesando campaña {$campaign['name']}: " . $e->getMessage());
            return [
                'success' => false,
                'reconciled' => false,
                'details' => null,
                'error' => "Error en campaña {$campaign['name']}: " . $e->getMessage()
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
        // Intentar obtener el presupuesto diario de diferentes campos
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
            return Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
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
        // Intentar extraer el nombre del cliente del nombre de la campaña
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
     * Detectar automáticamente el plan de publicidad basado en la información de la campaña
     */
    private function detectAdvertisingPlan(array $campaignInfo): ?AdvertisingPlan
    {
        $dailyBudget = $campaignInfo['daily_budget'];
        $durationDays = $campaignInfo['duration_days'];
        
        // Buscar planes que coincidan con el presupuesto diario y duración
        $matchingPlans = AdvertisingPlan::active()
            ->where('daily_budget', $dailyBudget)
            ->where('duration_days', $durationDays)
            ->get();
        
        if ($matchingPlans->count() === 1) {
            return $matchingPlans->first();
        }
        
        // Si hay múltiples coincidencias, usar el más reciente
        if ($matchingPlans->count() > 1) {
            return $matchingPlans->sortByDesc('created_at')->first();
        }
        
        // Si no hay coincidencia exacta, buscar el más cercano
        $closestPlan = AdvertisingPlan::active()
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
     * Crear la conciliación de la campaña
     */
    private function createReconciliation(FacebookAccount $account, array $campaign, array $campaignInfo, ?AdvertisingPlan $plan): CampaignReconciliation
    {
        $reconciliation = CampaignReconciliation::create([
            'facebook_account_id' => $account->id,
            'advertising_plan_id' => $plan?->id,
            'meta_campaign_id' => $campaign['id'],
            'meta_campaign_name' => $campaign['name'],
            'client_name' => $campaignInfo['client_name'],
            'client_type' => 'fanpage', // Por defecto
            'daily_budget' => $campaignInfo['daily_budget'],
            'duration_days' => $campaignInfo['duration_days'],
            'total_budget' => $campaignInfo['total_budget'],
            'client_price' => $plan?->client_price,
            'profit_margin' => $plan?->profit_margin,
            'actual_spend' => 0,
            'remaining_budget' => $campaignInfo['total_budget'],
            'status' => 'pending',
            'campaign_start_date' => $campaignInfo['start_date'],
            'campaign_end_date' => $campaignInfo['end_date'],
            'meta_data' => $campaign,
            'notes' => $plan ? "Plan detectado automáticamente: {$plan->plan_name}" : "Sin plan detectado"
        ]);

        Log::info("Conciliación creada para campaña {$campaign['name']}", [
            'reconciliation_id' => $reconciliation->id,
            'plan_detected' => $plan ? $plan->plan_name : 'Ninguno',
            'client_name' => $campaignInfo['client_name']
        ]);

        return $reconciliation;
    }

    /**
     * Crear transacciones contables para la conciliación
     */
    private function createAccountingTransactions(CampaignReconciliation $reconciliation, AdvertisingPlan $plan): void
    {
        // Transacción de ingreso (lo que paga el cliente)
        AccountingTransaction::create([
            'campaign_reconciliation_id' => $reconciliation->id,
            'advertising_plan_id' => $plan->id,
            'transaction_type' => 'income',
            'description' => "Pago por plan {$plan->plan_name} - Cliente: {$reconciliation->client_name}",
            'amount' => $plan->client_price,
            'currency' => 'USD',
            'status' => 'completed',
            'client_name' => $reconciliation->client_name,
            'meta_campaign_id' => $reconciliation->meta_campaign_id,
            'transaction_date' => now(),
            'notes' => 'Transacción automática por conciliación de campaña'
        ]);

        // Transacción de gasto (presupuesto de Meta)
        AccountingTransaction::create([
            'campaign_reconciliation_id' => $reconciliation->id,
            'advertising_plan_id' => $plan->id,
            'transaction_type' => 'expense',
            'description' => "Presupuesto Meta Ads para plan {$plan->plan_name}",
            'amount' => $plan->total_budget,
            'currency' => 'USD',
            'status' => 'pending',
            'client_name' => $reconciliation->client_name,
            'meta_campaign_id' => $reconciliation->meta_campaign_id,
            'transaction_date' => now(),
            'notes' => 'Gasto esperado en Meta Ads'
        ]);

        // Transacción de ganancia
        AccountingTransaction::create([
            'campaign_reconciliation_id' => $reconciliation->id,
            'advertising_plan_id' => $plan->id,
            'transaction_type' => 'profit',
            'description' => "Ganancia del plan {$plan->plan_name}",
            'amount' => $plan->profit_margin,
            'currency' => 'USD',
            'status' => 'pending',
            'client_name' => $reconciliation->client_name,
            'meta_campaign_id' => $reconciliation->meta_campaign_id,
            'transaction_date' => now(),
            'notes' => 'Ganancia esperada del plan'
        ]);

        Log::info("Transacciones contables creadas para conciliación {$reconciliation->id}", [
            'plan' => $plan->plan_name,
            'client' => $reconciliation->client_name,
            'transactions_count' => 3
        ]);
    }

    /**
     * Obtener campañas desde Meta API
     */
    private function getMetaCampaigns(FacebookAccount $account): array
    {
        try {
            // Aquí implementarías la llamada real a Meta API
            // Por ahora retornamos un array vacío como placeholder
            return [];
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo campañas de Meta para cuenta {$account->account_name}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar gasto real de una campaña conciliada
     */
    public function updateCampaignSpend(string $metaCampaignId, float $newSpend): bool
    {
        try {
            $reconciliation = CampaignReconciliation::where('meta_campaign_id', $metaCampaignId)->first();
            
            if (!$reconciliation) {
                Log::warning("No se encontró conciliación para campaña Meta: {$metaCampaignId}");
                return false;
            }
            
            $reconciliation->updateActualSpend($newSpend);
            
            // Actualizar transacción de gasto
            $expenseTransaction = $reconciliation->accountingTransactions()
                ->where('transaction_type', 'expense')
                ->first();
            
            if ($expenseTransaction) {
                $expenseTransaction->update([
                    'amount' => $newSpend,
                    'status' => $newSpend >= $reconciliation->total_budget ? 'completed' : 'pending'
                ]);
            }
            
            Log::info("Gasto actualizado para campaña {$metaCampaignId}: {$newSpend}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error actualizando gasto para campaña {$metaCampaignId}: " . $e->getMessage());
            return false;
        }
    }
}
