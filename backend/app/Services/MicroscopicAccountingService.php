<?php

namespace App\Services;

use App\Models\ActiveCampaign;
use App\Models\CampaignPlanReconciliation;
use App\Models\AccountingTransaction;
use App\Models\AdvertisingPlan;
use Illuminate\Support\Facades\Log;

class MicroscopicAccountingService
{
    /**
     * Procesar campañas con contabilidad microscópica basada en estado
     */
    public function processCampaignsByStatus(): array
    {
        $results = [
            'active' => ['processed' => 0, 'reconciled' => 0, 'errors' => []],
            'paused' => ['processed' => 0, 'reconciled' => 0, 'errors' => []],
            'scheduled' => ['processed' => 0, 'reconciled' => 0, 'errors' => []],
            'completed' => ['processed' => 0, 'reconciled' => 0, 'errors' => []],
            'summary' => []
        ];

        try {
            $activeCampaigns = ActiveCampaign::all();
            
            foreach ($activeCampaigns as $campaign) {
                $realStatus = strtolower($campaign->getRealCampaignStatus());
                $campaignResult = $this->processCampaignByStatus($campaign, $realStatus);
                
                $results[$realStatus]['processed']++;
                
                if ($campaignResult['success']) {
                    if ($campaignResult['reconciled']) {
                        $results[$realStatus]['reconciled']++;
                    }
                } else {
                    $results[$realStatus]['errors'][] = $campaignResult['error'];
                }
            }
            
            // Crear resumen
            $results['summary'] = $this->createSummary($results);
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento microscópico de campañas: ' . $e->getMessage());
            $results['error'] = 'Error general: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Procesar una campaña individual basada en su estado
     */
    private function processCampaignByStatus(ActiveCampaign $campaign, string $status): array
    {
        try {
            // Verificar si ya existe una conciliación para esta campaña
            $existingReconciliation = CampaignPlanReconciliation::where('active_campaign_id', $campaign->id)->first();
            
            if ($existingReconciliation) {
                return [
                    'success' => true,
                    'reconciled' => false,
                    'details' => "Campaña {$campaign->meta_campaign_name} ya conciliada (Estado: {$status})",
                    'error' => null
                ];
            }

            switch ($status) {
                case 'active':
                    return $this->processActiveCampaign($campaign);
                
                case 'paused':
                    return $this->processPausedCampaign($campaign);
                
                case 'scheduled':
                    return $this->processScheduledCampaign($campaign);
                
                case 'completed':
                    return $this->processCompletedCampaign($campaign);
                
                default:
                    return [
                        'success' => false,
                        'reconciled' => false,
                        'details' => null,
                        'error' => "Estado no reconocido: {$status} para campaña {$campaign->meta_campaign_name}"
                    ];
            }

        } catch (\Exception $e) {
            Log::error("Error procesando campaña {$campaign->meta_campaign_name} (Estado: {$status}): " . $e->getMessage());
            return [
                'success' => false,
                'reconciled' => false,
                'details' => null,
                'error' => "Error en campaña {$campaign->meta_campaign_name}: " . $e->getMessage()
            ];
        }
    }

    /**
     * Procesar campaña ACTIVA - Conciliación normal
     */
    private function processActiveCampaign(ActiveCampaign $campaign): array
    {
        $reconciliationService = new CampaignReconciliationService();
        
        // Extraer información de la campaña
        $campaignInfo = $reconciliationService->extractCampaignInfo($campaign);
        
        // Detectar automáticamente el plan de publicidad
        $detectedPlan = $reconciliationService->detectAdvertisingPlan($campaignInfo);
        
        // Crear la conciliación normal
        $reconciliation = $reconciliationService->createReconciliation($campaign, $campaignInfo, $detectedPlan);
        
        // Crear transacciones contables si se detectó un plan
        if ($detectedPlan) {
            $reconciliationService->createAccountingTransactions($reconciliation, $detectedPlan);
        }

        return [
            'success' => true,
            'reconciled' => true,
            'details' => "Campaña ACTIVA {$campaign->meta_campaign_name} conciliada con plan: " . ($detectedPlan ? $detectedPlan->plan_name : 'Sin plan detectado'),
            'error' => null
        ];
    }

    /**
     * Procesar campaña PAUSADA - Solo registrar gasto real
     */
    private function processPausedCampaign(ActiveCampaign $campaign): array
    {
        $reconciliationService = new CampaignReconciliationService();
        
        // Extraer información de la campaña
        $campaignInfo = $reconciliationService->extractCampaignInfo($campaign);
        
        // Detectar automáticamente el plan de publicidad
        $detectedPlan = $reconciliationService->detectAdvertisingPlan($campaignInfo);
        
        // Crear conciliación especial para campaña pausada
        $reconciliation = $this->createPausedReconciliation($campaign, $campaignInfo, $detectedPlan);
        
        // Crear transacción contable especial para campaña pausada
        $this->createPausedAccountingTransaction($reconciliation, $campaignInfo);

        return [
            'success' => true,
            'reconciled' => true,
            'details' => "Campaña PAUSADA {$campaign->meta_campaign_name} - Solo gasto real registrado: $" . number_format($campaignInfo['actual_spent'], 2),
            'error' => null
        ];
    }

    /**
     * Procesar campaña PROGRAMADA - No crear conciliación aún
     */
    private function processScheduledCampaign(ActiveCampaign $campaign): array
    {
        // Solo registrar en logs, no crear conciliación
        Log::info("Campaña PROGRAMADA detectada: {$campaign->meta_campaign_name} - Inicio: " . 
                 ($campaign->campaign_start_time?->format('Y-m-d H:i:s') ?? 'No definido'));

        return [
            'success' => true,
            'reconciled' => false,
            'details' => "Campaña PROGRAMADA {$campaign->meta_campaign_name} - Esperando activación",
            'error' => null
        ];
    }

    /**
     * Procesar campaña COMPLETADA - Conciliación final
     */
    private function processCompletedCampaign(ActiveCampaign $campaign): array
    {
        $reconciliationService = new CampaignReconciliationService();
        
        // Extraer información de la campaña
        $campaignInfo = $reconciliationService->extractCampaignInfo($campaign);
        
        // Detectar automáticamente el plan de publicidad
        $detectedPlan = $reconciliationService->detectAdvertisingPlan($campaignInfo);
        
        // Crear conciliación final
        $reconciliation = $this->createCompletedReconciliation($campaign, $campaignInfo, $detectedPlan);
        
        // Crear transacciones contables finales
        if ($detectedPlan) {
            $reconciliationService->createAccountingTransactions($reconciliation, $detectedPlan);
        }

        return [
            'success' => true,
            'reconciled' => true,
            'details' => "Campaña COMPLETADA {$campaign->meta_campaign_name} - Conciliación final realizada",
            'error' => null
        ];
    }

    /**
     * Crear conciliación especial para campaña pausada
     */
    private function createPausedReconciliation(ActiveCampaign $campaign, array $campaignInfo, ?AdvertisingPlan $plan): CampaignPlanReconciliation
    {
        // Si no hay plan detectado, crear un plan personalizado
        if (!$plan) {
            $plan = $this->createCustomPlan($campaignInfo);
        }

        $reconciliation = CampaignPlanReconciliation::create([
            'active_campaign_id' => $campaign->id,
            'advertising_plan_id' => $plan->id,
            'reconciliation_status' => 'paused', // Estado especial para pausadas
            'reconciliation_date' => now(),
            'planned_budget' => $campaignInfo['actual_spent'], // Solo lo que realmente se gastó
            'actual_spent' => $campaignInfo['actual_spent'],
            'variance' => 0, // No hay variación porque solo registramos lo gastado
            'variance_percentage' => 0,
            'notes' => "Campaña PAUSADA - Solo gasto real registrado: $" . number_format($campaignInfo['actual_spent'], 2) . 
                      " (Plan original: " . ($plan->plan_name) . " - Presupuesto total: $" . number_format($plan->total_budget, 2) . ")",
            'reconciliation_data' => [
                'campaign_info' => $campaignInfo,
                'detection_method' => 'paused_campaign',
                'detected_at' => now()->toISOString(),
                'original_plan' => $plan->toArray(),
                'paused_reason' => 'Campaña pausada antes de completar el plan'
            ],
            'last_updated_at' => now(),
        ]);

        Log::info("Conciliación PAUSADA creada para campaña {$campaign->meta_campaign_name}", [
            'reconciliation_id' => $reconciliation->id,
            'actual_spent' => $campaignInfo['actual_spent'],
            'original_plan' => $plan->plan_name
        ]);

        return $reconciliation;
    }

    /**
     * Crear transacción contable especial para campaña pausada
     */
    private function createPausedAccountingTransaction(CampaignPlanReconciliation $reconciliation, array $campaignInfo): void
    {
        $reconciliationService = new CampaignReconciliationService();
        
        // Detectar automáticamente el nombre de Instagram del cliente
        $instagramClientName = $reconciliationService->extractClientName($reconciliation->activeCampaign);
        
        // Obtener fechas reales de la campaña activa
        $campaignStartDate = $reconciliation->activeCampaign->campaign_start_time?->format('Y-m-d');
        $campaignEndDate = $reconciliation->activeCampaign->campaign_stop_time?->format('Y-m-d');

        // Crear transacción especial para campaña pausada
        AccountingTransaction::create([
            'campaign_reconciliation_id' => $reconciliation->id,
            'advertising_plan_id' => $reconciliation->advertising_plan_id,
            'description' => "Campaña PAUSADA - Solo gasto real - Cliente: {$instagramClientName}",
            'income' => 0, // No hay ingreso porque la campaña no se completó
            'expense' => $campaignInfo['actual_spent'], // Solo lo que realmente se gastó
            'profit' => -$campaignInfo['actual_spent'], // Pérdida porque no se completó el plan
            'currency' => 'USD',
            'status' => 'paused', // Estado especial
            'client_name' => $instagramClientName,
            'meta_campaign_id' => $reconciliation->activeCampaign->meta_campaign_id,
            'campaign_start_date' => $campaignStartDate,
            'campaign_end_date' => $campaignEndDate,
            'transaction_date' => now(),
            'notes' => 'Campaña pausada - Solo se registra el gasto real sin completar el plan de publicidad',
            'metadata' => [
                'transaction_type' => 'paused_campaign',
                'original_plan' => $reconciliation->advertisingPlan->plan_name,
                'original_plan_budget' => $reconciliation->advertisingPlan->total_budget,
                'actual_spent' => $campaignInfo['actual_spent'],
                'loss_amount' => $campaignInfo['actual_spent'],
                'paused_at' => now()->toISOString(),
                'instagram_detected' => $instagramClientName !== 'Cliente Sin Identificar',
                'campaign_dates' => [
                    'start_date' => $campaignStartDate,
                    'end_date' => $campaignEndDate,
                    'duration_days' => $reconciliation->activeCampaign->getCampaignDurationDays()
                ]
            ]
        ]);

        Log::info("Transacción contable PAUSADA creada para conciliación {$reconciliation->id}", [
            'campaign' => $reconciliation->activeCampaign->meta_campaign_name,
            'instagram_client' => $instagramClientName,
            'actual_spent' => $campaignInfo['actual_spent'],
            'loss_amount' => $campaignInfo['actual_spent']
        ]);
    }

    /**
     * Crear conciliación final para campaña completada
     */
    private function createCompletedReconciliation(ActiveCampaign $campaign, array $campaignInfo, ?AdvertisingPlan $plan): CampaignPlanReconciliation
    {
        // Si no hay plan detectado, crear un plan personalizado
        if (!$plan) {
            $plan = $this->createCustomPlan($campaignInfo);
        }

        $reconciliation = CampaignPlanReconciliation::create([
            'active_campaign_id' => $campaign->id,
            'advertising_plan_id' => $plan->id,
            'reconciliation_status' => 'completed', // Estado especial para completadas
            'reconciliation_date' => now(),
            'planned_budget' => $plan->total_budget,
            'actual_spent' => $campaignInfo['actual_spent'],
            'variance' => $plan->total_budget - $campaignInfo['actual_spent'],
            'variance_percentage' => $plan->total_budget > 0 ? 
                (($plan->total_budget - $campaignInfo['actual_spent']) / $plan->total_budget) * 100 : 0,
            'notes' => "Campaña COMPLETADA - Conciliación final: Plan {$plan->plan_name} - " .
                      "Presupuesto: $" . number_format($plan->total_budget, 2) . 
                      " - Gastado: $" . number_format($campaignInfo['actual_spent'], 2),
            'reconciliation_data' => [
                'campaign_info' => $campaignInfo,
                'detection_method' => 'completed_campaign',
                'detected_at' => now()->toISOString(),
                'completion_date' => $campaign->campaign_stop_time?->format('Y-m-d H:i:s')
            ],
            'last_updated_at' => now(),
        ]);

        Log::info("Conciliación COMPLETADA creada para campaña {$campaign->meta_campaign_name}", [
            'reconciliation_id' => $reconciliation->id,
            'plan' => $plan->plan_name,
            'planned_budget' => $plan->total_budget,
            'actual_spent' => $campaignInfo['actual_spent']
        ]);

        return $reconciliation;
    }

    /**
     * Crear un plan personalizado (reutilizar lógica del servicio original)
     */
    private function createCustomPlan(array $campaignInfo): AdvertisingPlan
    {
        $reconciliationService = new CampaignReconciliationService();
        return $reconciliationService->createCustomPlan($campaignInfo);
    }

    /**
     * Crear resumen de procesamiento
     */
    private function createSummary(array $results): array
    {
        $totalProcessed = 0;
        $totalReconciled = 0;
        $totalErrors = 0;

        foreach (['active', 'paused', 'scheduled', 'completed'] as $status) {
            $totalProcessed += $results[$status]['processed'];
            $totalReconciled += $results[$status]['reconciled'];
            $totalErrors += count($results[$status]['errors']);
        }

        return [
            'total_campaigns_processed' => $totalProcessed,
            'total_campaigns_reconciled' => $totalReconciled,
            'total_errors' => $totalErrors,
            'success_rate' => $totalProcessed > 0 ? ($totalReconciled / $totalProcessed) * 100 : 0,
            'status_breakdown' => [
                'active' => $results['active']['processed'],
                'paused' => $results['paused']['processed'],
                'scheduled' => $results['scheduled']['processed'],
                'completed' => $results['completed']['processed']
            ]
        ];
    }
}
