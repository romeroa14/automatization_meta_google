<?php

namespace App\Services;

use App\Models\AdvertisingPlan;
use App\Models\CampaignPlanReconciliation;
use App\Models\ActiveCampaign;
use App\Models\AccountingTransaction;
use Illuminate\Support\Facades\Log;

class CampaignReconciliationService
{
    /**
     * Procesar todas las campañas activas y crear conciliaciones automáticas
     */
    public function processActiveCampaigns(): array
    {
        $results = [
            'processed' => 0,
            'reconciled' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            // Obtener todas las campañas activas
            $activeCampaigns = ActiveCampaign::all();
            
            foreach ($activeCampaigns as $campaign) {
                $campaignResult = $this->processSingleActiveCampaign($campaign);
                
                $results['processed']++;
                
                if ($campaignResult['success']) {
                    if ($campaignResult['reconciled']) {
                        $results['reconciled']++;
                    }
                    $results['details'][] = $campaignResult['details'];
                } else {
                    $results['errors'][] = $campaignResult['error'];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento automático de campañas: ' . $e->getMessage());
            $results['errors'][] = 'Error general: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Procesar una campaña activa individual
     */
    private function processSingleActiveCampaign(ActiveCampaign $campaign): array
    {
        try {
            // Verificar si ya existe una conciliación para esta campaña
            $existingReconciliation = CampaignPlanReconciliation::where('active_campaign_id', $campaign->id)->first();
            
            if ($existingReconciliation) {
                return [
                    'success' => true,
                    'reconciled' => false,
                    'details' => "Campaña {$campaign->meta_campaign_name} ya conciliada",
                    'error' => null
                ];
            }

            // Extraer información de la campaña
            $campaignInfo = $this->extractCampaignInfo($campaign);
            
            // Detectar automáticamente el plan de publicidad
            $detectedPlan = $this->detectAdvertisingPlan($campaignInfo);
            
            // Crear la conciliación
            $reconciliation = $this->createReconciliation($campaign, $campaignInfo, $detectedPlan);
            
            // Crear transacciones contables si se detectó un plan
            if ($detectedPlan) {
                $this->createAccountingTransactions($reconciliation, $detectedPlan);
            }

            return [
                'success' => true,
                'reconciled' => true,
                'details' => "Campaña {$campaign->meta_campaign_name} conciliada con plan: " . ($detectedPlan ? $detectedPlan->plan_name : 'Sin plan detectado'),
                'error' => null
            ];

        } catch (\Exception $e) {
            Log::error("Error procesando campaña {$campaign->meta_campaign_name}: " . $e->getMessage());
            return [
                'success' => false,
                'reconciled' => false,
                'details' => null,
                'error' => "Error en campaña {$campaign->meta_campaign_name}: " . $e->getMessage()
            ];
        }
    }

    /**
     * Extraer información relevante de la campaña activa
     */
    private function extractCampaignInfo(ActiveCampaign $campaign): array
    {
        // Obtener presupuesto diario (convertido de centavos)
        $dailyBudget = $campaign->campaign_daily_budget ?? $campaign->adset_daily_budget ?? 0;
        if ($dailyBudget > 100) {
            $dailyBudget = $dailyBudget / 100;
        }

        // Obtener duración en días (redondear hacia abajo)
        $durationDays = $campaign->getCampaignDurationDays() ?? $campaign->getAdsetDurationDays() ?? 0;
        $durationDays = floor($durationDays); // Redondear hacia abajo (15.99 → 15)

        // Obtener presupuesto total
        $totalBudget = $campaign->campaign_total_budget ?? $campaign->adset_lifetime_budget ?? 0;
        if ($totalBudget > 100) {
            $totalBudget = $totalBudget / 100;
        }

        // Obtener gasto actual
        $actualSpent = $campaign->getAmountSpentFromMeta() ?? $campaign->getAmountSpentEstimated() ?? 0;

        return [
            'daily_budget' => (float) $dailyBudget,
            'duration_days' => (int) $durationDays,
            'total_budget' => (float) $totalBudget,
            'actual_spent' => (float) $actualSpent,
            'campaign_name' => $campaign->meta_campaign_name,
            'client_name' => $this->extractClientName($campaign->meta_campaign_name),
            'start_date' => $campaign->campaign_start_time?->format('Y-m-d'),
            'end_date' => $campaign->campaign_stop_time?->format('Y-m-d'),
        ];
    }

    /**
     * Extraer nombre del cliente del nombre de la campaña
     */
    private function extractClientName(string $campaignName): string
    {
        // Limpiar el nombre de la campaña
        $cleanName = trim($campaignName);
        
        // Buscar patrones comunes en nombres de campaña
        // Patrón 1: Nombre al inicio seguido de fecha o separador
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/', $cleanName, $matches)) {
            $clientName = trim($matches[1]);
            // Verificar que no sea solo números o caracteres especiales
            if (strlen($clientName) > 2 && !preg_match('/^[\d\s\-\|\$]+$/', $clientName)) {
                return $clientName;
            }
        }
        
        // Patrón 2: Buscar después de "Publicación:" o "Publicación de Instagram:"
        if (preg_match('/(?:Publicación(?:\s+de\s+Instagram)?:\s*["\']?)([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/', $cleanName, $matches)) {
            $clientName = trim($matches[1]);
            if (strlen($clientName) > 2 && !preg_match('/^[\d\s\-\|\$]+$/', $clientName)) {
                return $clientName;
            }
        }
        
        // Patrón 3: Buscar cualquier palabra que empiece con mayúscula seguida de minúsculas
        if (preg_match('/\b([A-Z][a-z]{2,})\b/', $cleanName, $matches)) {
            $clientName = trim($matches[1]);
            // Excluir palabras comunes que no son nombres de cliente
            $excludeWords = ['Publicación', 'Instagram', 'Facebook', 'Meta', 'Ads', 'Campaña', 'Campaign'];
            if (!in_array($clientName, $excludeWords)) {
                return $clientName;
            }
        }
        
        // Si no se puede extraer un nombre válido, usar el nombre completo truncado
        $truncatedName = substr($cleanName, 0, 30);
        return $truncatedName ?: 'Cliente Sin Identificar';
    }

    /**
     * Detectar automáticamente el plan de publicidad basado en la información de la campaña
     */
    private function detectAdvertisingPlan(array $campaignInfo): ?AdvertisingPlan
    {
        $dailyBudget = $campaignInfo['daily_budget'];
        $durationDays = $campaignInfo['duration_days'];
        
        // Buscar planes que coincidan exactamente con el presupuesto diario y duración
        $exactMatch = AdvertisingPlan::where('daily_budget', $dailyBudget)
            ->where('duration_days', $durationDays)
            ->first();
        
        if ($exactMatch) {
            return $exactMatch;
        }
        
        // Si no hay coincidencia exacta, buscar el más cercano
        $closestPlan = AdvertisingPlan::all()
            ->sortBy(function ($plan) use ($dailyBudget, $durationDays) {
                $budgetDiff = abs($plan->daily_budget - $dailyBudget);
                $durationDiff = abs($plan->duration_days - $durationDays);
                return $budgetDiff + $durationDiff;
            })
            ->first();
        
        // Solo usar si la diferencia es razonable (tolerancia pequeña)
        if ($closestPlan) {
            $budgetDiff = abs($closestPlan->daily_budget - $dailyBudget);
            $durationDiff = abs($closestPlan->duration_days - $durationDays);
            
            // Tolerancia: presupuesto ±$0.50, duración ±1 día
            if ($budgetDiff <= 0.50 && $durationDiff <= 1) {
                return $closestPlan;
            }
        }
        
        return null;
    }

    /**
     * Crear la conciliación de la campaña
     */
    private function createReconciliation(ActiveCampaign $campaign, array $campaignInfo, ?AdvertisingPlan $plan): CampaignPlanReconciliation
    {
        $reconciliation = CampaignPlanReconciliation::create([
            'active_campaign_id' => $campaign->id,
            'advertising_plan_id' => $plan?->id,
            'reconciliation_status' => 'pending',
            'reconciliation_date' => now(),
            'planned_budget' => $plan?->total_budget ?? $campaignInfo['total_budget'],
            'actual_spent' => $campaignInfo['actual_spent'],
            'variance' => $plan ? ($plan->total_budget - $campaignInfo['actual_spent']) : 0,
            'variance_percentage' => $plan && $plan->total_budget > 0 ? 
                (($plan->total_budget - $campaignInfo['actual_spent']) / $plan->total_budget) * 100 : 0,
            'notes' => $plan ? 
                "Plan detectado automáticamente: {$plan->plan_name} (Presupuesto: $" . number_format($campaignInfo['daily_budget'], 2) . "/día, Duración: {$campaignInfo['duration_days']} días)" : 
                "Sin plan detectado (Presupuesto: $" . number_format($campaignInfo['daily_budget'], 2) . "/día, Duración: {$campaignInfo['duration_days']} días)",
            'reconciliation_data' => [
                'campaign_info' => $campaignInfo,
                'detection_method' => 'automatic',
                'detected_at' => now()->toISOString(),
            ],
            'last_updated_at' => now(),
        ]);

        // Calcular variación automáticamente
        $reconciliation->calculateVariance();

        Log::info("Conciliación creada para campaña {$campaign->meta_campaign_name}", [
            'reconciliation_id' => $reconciliation->id,
            'plan_detected' => $plan ? $plan->plan_name : 'Ninguno',
            'daily_budget' => $campaignInfo['daily_budget'],
            'duration_days' => $campaignInfo['duration_days']
        ]);

        return $reconciliation;
    }

    /**
     * Crear transacciones contables para la conciliación
     */
    private function createAccountingTransactions(CampaignPlanReconciliation $reconciliation, AdvertisingPlan $plan): void
    {
        // Transacción de ingreso (lo que paga el cliente)
        AccountingTransaction::create([
            'campaign_reconciliation_id' => $reconciliation->id,
            'advertising_plan_id' => $plan->id,
            'transaction_type' => 'income',
            'description' => "Pago por plan {$plan->plan_name} - Cliente: {$reconciliation->activeCampaign->meta_campaign_name}",
            'amount' => $plan->client_price,
            'currency' => 'USD',
            'status' => 'completed',
            'client_name' => $reconciliation->activeCampaign->meta_campaign_name,
            'meta_campaign_id' => $reconciliation->activeCampaign->meta_campaign_id,
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
            'client_name' => $reconciliation->activeCampaign->meta_campaign_name,
            'meta_campaign_id' => $reconciliation->activeCampaign->meta_campaign_id,
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
            'client_name' => $reconciliation->activeCampaign->meta_campaign_name,
            'meta_campaign_id' => $reconciliation->activeCampaign->meta_campaign_id,
            'transaction_date' => now(),
            'notes' => 'Ganancia esperada del plan'
        ]);

        Log::info("Transacciones contables creadas para conciliación {$reconciliation->id}", [
            'plan' => $plan->plan_name,
            'campaign' => $reconciliation->activeCampaign->meta_campaign_name,
            'transactions_count' => 3
        ]);
    }

    /**
     * Actualizar gasto real de una campaña conciliada
     */
    public function updateCampaignSpend(int $activeCampaignId, float $newSpend): bool
    {
        try {
            $reconciliation = CampaignPlanReconciliation::where('active_campaign_id', $activeCampaignId)->first();
            
            if (!$reconciliation) {
                Log::warning("No se encontró conciliación para campaña activa ID: {$activeCampaignId}");
                return false;
            }
            
            $reconciliation->update([
                'actual_spent' => $newSpend,
                'last_updated_at' => now(),
            ]);
            
            // Recalcular variación
            $reconciliation->calculateVariance();
            
            // Actualizar transacción de gasto
            $expenseTransaction = AccountingTransaction::where('campaign_reconciliation_id', $reconciliation->id)
                ->where('transaction_type', 'expense')
                ->first();
            
            if ($expenseTransaction) {
                $expenseTransaction->update([
                    'amount' => $newSpend,
                    'status' => $newSpend >= $reconciliation->planned_budget ? 'completed' : 'pending'
                ]);
            }
            
            Log::info("Gasto actualizado para campaña activa ID {$activeCampaignId}: {$newSpend}");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error actualizando gasto para campaña activa ID {$activeCampaignId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de conciliaciones
     */
    public function getReconciliationStats(): array
    {
        $totalReconciliations = CampaignPlanReconciliation::count();
        $pendingReconciliations = CampaignPlanReconciliation::pending()->count();
        $completedReconciliations = CampaignPlanReconciliation::completed()->count();
        $totalVariance = CampaignPlanReconciliation::sum('variance');
        $totalPlannedBudget = CampaignPlanReconciliation::sum('planned_budget');
        $totalActualSpent = CampaignPlanReconciliation::sum('actual_spent');

        return [
            'total_reconciliations' => $totalReconciliations,
            'pending_reconciliations' => $pendingReconciliations,
            'completed_reconciliations' => $completedReconciliations,
            'total_variance' => $totalVariance,
            'total_planned_budget' => $totalPlannedBudget,
            'total_actual_spent' => $totalActualSpent,
            'variance_percentage' => $totalPlannedBudget > 0 ? ($totalVariance / $totalPlannedBudget) * 100 : 0,
        ];
    }
}