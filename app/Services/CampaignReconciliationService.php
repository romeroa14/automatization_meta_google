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
    public function extractCampaignInfo(ActiveCampaign $campaign): array
    {
        // Obtener presupuesto diario usando el método convertMetaNumber del modelo
        $dailyBudgetRaw = $campaign->campaign_daily_budget ?? $campaign->adset_daily_budget ?? 0;
        $dailyBudget = $campaign->convertMetaNumber($dailyBudgetRaw, 'budget');

        // Obtener duración en días (redondear hacia abajo)
        $durationDays = $campaign->getCampaignDurationDays() ?? $campaign->getAdsetDurationDays() ?? 0;
        $durationDays = floor($durationDays); // Redondear hacia abajo (15.99 → 15)

        // Obtener presupuesto total usando el método convertMetaNumber del modelo
        $totalBudgetRaw = $campaign->campaign_total_budget ?? $campaign->adset_lifetime_budget ?? 0;
        $totalBudget = $campaign->convertMetaNumber($totalBudgetRaw, 'budget');

        // Obtener gasto actual (convertir de centavos a dólares)
        $actualSpentRaw = $campaign->getAmountSpentFromMeta() ?? $campaign->getAmountSpentEstimated() ?? 0;
        $actualSpent = $campaign->convertMetaNumber($actualSpentRaw, 'amount');

        return [
            'daily_budget' => (float) $dailyBudget,
            'duration_days' => (int) $durationDays,
            'total_budget' => (float) $totalBudget,
            'actual_spent' => (float) $actualSpent,
            'campaign_name' => $campaign->meta_campaign_name,
            'client_name' => $this->extractClientName($campaign),
            'start_date' => $campaign->campaign_start_time?->format('Y-m-d'),
            'end_date' => $campaign->campaign_stop_time?->format('Y-m-d'),
        ];
    }

    /**
     * Extraer nombre del cliente (cuenta de Instagram) desde la API de Meta
     */
    public function extractClientName(ActiveCampaign $campaign): string
    {
        try {
            // Obtener el ID del anuncio
            $adId = $campaign->meta_ad_id;
            
            if (!$adId) {
                Log::warning("No se encontró meta_ad_id para la campaña: {$campaign->meta_campaign_name}");
                return $this->fallbackClientName($campaign->meta_campaign_name);
            }
            
            // Obtener la cuenta de Facebook asociada
            $facebookAccount = \App\Models\FacebookAccount::where('id', $campaign->facebook_account_id)->first();
            
            if (!$facebookAccount) {
                Log::warning("No se encontró FacebookAccount para la campaña: {$campaign->meta_campaign_name}");
                return $this->fallbackClientName($campaign->meta_campaign_name);
            }
            
            // Hacer llamada a la API de Meta para obtener información del anuncio
            $instagramAccountName = $this->getInstagramAccountFromAdId($adId, $facebookAccount);
            
            if ($instagramAccountName) {
                Log::info("Nombre de cuenta de Instagram obtenido: {$instagramAccountName} para anuncio: {$adId}");
                return $instagramAccountName;
            }
            
            Log::warning("No se pudo obtener el nombre de la cuenta de Instagram para el anuncio: {$adId}");
            return $this->fallbackClientName($campaign->meta_campaign_name);
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo nombre de cuenta de Instagram para campaña {$campaign->meta_campaign_name}: " . $e->getMessage());
            return $this->fallbackClientName($campaign->meta_campaign_name);
        }
    }
    
    /**
     * Obtener el nombre de la cuenta de Instagram desde la API de Meta usando el ID del anuncio
     */
    private function getInstagramAccountFromAdId(string $adId, \App\Models\FacebookAccount $facebookAccount): ?string
    {
        try {
            // Configurar la API de Facebook
            \FacebookAds\Api::init($facebookAccount->app_id, $facebookAccount->app_secret, $facebookAccount->access_token);
            
            // Obtener información del anuncio
            $ad = new \FacebookAds\Object\Ad($adId);
            $ad->read([
                'id',
                'name',
                'creative',
                'effective_status'
            ]);
            
            // Obtener información del creative
            $creativeData = $ad->creative;
            
            // Si creative es un array, obtener el ID y crear el objeto
            if (is_array($creativeData)) {
                $creativeId = $creativeData['id'] ?? null;
                if (!$creativeId) {
                    Log::warning("No se encontró ID del creative para el anuncio: {$adId}");
                    return null;
                }
                $creative = new \FacebookAds\Object\AdCreative($creativeId);
            } else {
                $creative = $creativeData;
            }
            
            $creative->read([
                'id',
                'name',
                'object_story_spec',
                'actor_id'
            ]);
            
            // Intentar obtener desde actor_id (puede ser página de Instagram)
            if ($creative->actor_id) {
                try {
                    // Intentar como página de Instagram primero
                    $page = new \FacebookAds\Object\Page($creative->actor_id);
                    $page->read(['id', 'name', 'instagram_business_account']);
                    
                    // Si tiene cuenta de Instagram asociada
                    if ($page->instagram_business_account) {
                        $instagramAccount = $page->instagram_business_account;
                        return $instagramAccount['username'] ?? $instagramAccount['name'] ?? $page->name;
                    }
                    
                    // Si no tiene cuenta de Instagram, usar el nombre de la página
                    return $page->name;
                    
                } catch (\Exception $e) {
                    Log::warning("Error obteniendo página para actor_id {$creative->actor_id}: " . $e->getMessage());
                }
            }
            
            // Intentar obtener desde object_story_spec
            if ($creative->object_story_spec) {
                $storySpec = $creative->object_story_spec;
                
                // Buscar en page_id
                if (isset($storySpec['page_id'])) {
                    try {
                        $page = new \FacebookAds\Object\Page($storySpec['page_id']);
                        $page->read(['id', 'name', 'instagram_business_account']);
                        
                        // Si tiene cuenta de Instagram asociada
                        if ($page->instagram_business_account) {
                            $instagramAccount = $page->instagram_business_account;
                            return $instagramAccount['username'] ?? $instagramAccount['name'] ?? $page->name;
                        }
                        
                        // Si no tiene cuenta de Instagram, usar el nombre de la página
                        return $page->name;
                        
                    } catch (\Exception $e) {
                        Log::warning("Error obteniendo página para page_id {$storySpec['page_id']}: " . $e->getMessage());
                    }
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo cuenta de Instagram para anuncio {$adId}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Método de respaldo para extraer nombre del cliente del texto de la campaña
     */
    private function fallbackClientName(string $campaignName): string
    {
        // Limpiar el nombre de la campaña
        $cleanName = trim($campaignName);
        
        // Patrón principal: Nombre de cuenta de Instagram antes del separador "|"
        if (preg_match('/^([^|]+?)\s*\|\s*/', $cleanName, $matches)) {
            $clientName = trim($matches[1]);
            
            // Limpiar sufijos comunes como "- Copia", " - Copia", etc.
            $clientName = preg_replace('/\s*-\s*Copia\s*$/i', '', $clientName);
            $clientName = preg_replace('/\s*\(Copia\)\s*$/i', '', $clientName);
            
            // Verificar que no esté vacío y tenga al menos 2 caracteres
            if (strlen($clientName) >= 2) {
                return $clientName;
            }
        }
        
        // Patrón secundario: Buscar nombre al inicio seguido de fecha
        if (preg_match('/^([A-Za-z0-9._-]+?)\s+\d{2}\/\d{2}\/\d{4}/', $cleanName, $matches)) {
            $clientName = trim($matches[1]);
            if (strlen($clientName) >= 2) {
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
    public function detectAdvertisingPlan(array $campaignInfo): ?AdvertisingPlan
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
    public function createReconciliation(ActiveCampaign $campaign, array $campaignInfo, ?AdvertisingPlan $plan): CampaignPlanReconciliation
    {
        // Si no hay plan detectado, crear un plan personalizado
        if (!$plan) {
            $plan = $this->createCustomPlan($campaignInfo);
        }

        $reconciliation = CampaignPlanReconciliation::create([
            'active_campaign_id' => $campaign->id,
            'advertising_plan_id' => $plan->id,
            'reconciliation_status' => 'pending',
            'reconciliation_date' => now(),
            'planned_budget' => $plan->total_budget,
            'actual_spent' => $campaignInfo['actual_spent'],
            'variance' => $plan->total_budget - $campaignInfo['actual_spent'],
            'variance_percentage' => $plan->total_budget > 0 ? 
                (($plan->total_budget - $campaignInfo['actual_spent']) / $plan->total_budget) * 100 : 0,
            'notes' => $plan->plan_name === 'Plan Personalizado' ? 
                "Plan personalizado creado automáticamente (Presupuesto: $" . number_format($campaignInfo['daily_budget'], 2) . "/día, Duración: {$campaignInfo['duration_days']} días) - Requiere configuración de ganancia" : 
                "Plan detectado automáticamente: {$plan->plan_name} (Presupuesto: $" . number_format($campaignInfo['daily_budget'], 2) . "/día, Duración: {$campaignInfo['duration_days']} días)",
            'reconciliation_data' => [
                'campaign_info' => $campaignInfo,
                'detection_method' => $plan->plan_name === 'Plan Personalizado' ? 'custom_created' : 'automatic',
                'detected_at' => now()->toISOString(),
                'instagram_client_name' => $campaignInfo['client_name'], // Guardar el nombre de Instagram detectado
            ],
            'last_updated_at' => now(),
        ]);

        // Calcular variación automáticamente
        $reconciliation->calculateVariance();

        Log::info("Conciliación creada para campaña {$campaign->meta_campaign_name}", [
            'reconciliation_id' => $reconciliation->id,
            'plan_detected' => $plan->plan_name,
            'daily_budget' => $campaignInfo['daily_budget'],
            'duration_days' => $campaignInfo['duration_days'],
            'is_custom_plan' => $plan->plan_name === 'Plan Personalizado'
        ]);

        return $reconciliation;
    }

    /**
     * Crear un plan personalizado para campañas que no coinciden con planes existentes
     */
    public function createCustomPlan(array $campaignInfo): AdvertisingPlan
    {
        $dailyBudget = $campaignInfo['daily_budget'];
        $durationDays = $campaignInfo['duration_days'];
        $totalBudget = $dailyBudget * $durationDays;
        
        // Crear nombre único para el plan personalizado
        $planName = "Plan Personalizado - $" . number_format($dailyBudget, 2) . "/día x {$durationDays} días";
        
        // Verificar si ya existe un plan personalizado con estas características
        $existingPlan = AdvertisingPlan::where('plan_name', $planName)->first();
        
        if ($existingPlan) {
            return $existingPlan;
        }
        
        // Crear nuevo plan personalizado
        $customPlan = AdvertisingPlan::create([
            'plan_name' => $planName,
            'description' => "Plan personalizado creado automáticamente para campaña con presupuesto de $" . number_format($dailyBudget, 2) . " diarios por {$durationDays} días",
            'daily_budget' => $dailyBudget,
            'duration_days' => $durationDays,
            'total_budget' => $totalBudget,
            'client_price' => $totalBudget, // Inicialmente igual al presupuesto total (sin ganancia)
            'profit_margin' => 0, // Sin ganancia inicial
            'profit_percentage' => 0, // Sin ganancia inicial
            'is_active' => true,
            'features' => [
                'Facebook Ads' => 'Campaña publicitaria en Facebook',
                'Instagram Ads' => 'Campaña publicitaria en Instagram',
                'Reportes Básicos' => 'Reportes de rendimiento básicos',
                'Soporte' => 'Soporte técnico básico'
            ]
        ]);
        
        Log::info("Plan personalizado creado: {$planName}", [
            'plan_id' => $customPlan->id,
            'daily_budget' => $dailyBudget,
            'duration_days' => $durationDays,
            'total_budget' => $totalBudget
        ]);
        
        return $customPlan;
    }

    /**
     * Crear transacciones contables para la conciliación
     */
    public function createAccountingTransactions(CampaignPlanReconciliation $reconciliation, AdvertisingPlan $plan): void
    {
        $isCustomPlan = str_contains($plan->plan_name, 'Plan Personalizado');
        
        // Detectar automáticamente el nombre de Instagram del cliente
        $instagramClientName = $this->extractClientName($reconciliation->activeCampaign);
        
        // Obtener fechas reales de la campaña activa
        $campaignStartDate = $reconciliation->activeCampaign->campaign_start_time?->format('Y-m-d');
        $campaignEndDate = $reconciliation->activeCampaign->campaign_stop_time?->format('Y-m-d');

        // Crear UNA SOLA transacción consolidada con los 3 niveles
        AccountingTransaction::create([
            'campaign_reconciliation_id' => $reconciliation->id,
            'advertising_plan_id' => $plan->id,
            'description' => "Conciliación completa - Plan {$plan->plan_name} - Cliente: {$instagramClientName}",
            'income' => $plan->client_price, // Lo que paga el cliente
            'expense' => $plan->total_budget, // Presupuesto de Meta
            'profit' => $plan->profit_margin, // Ganancia
            'currency' => 'USD',
            'status' => $isCustomPlan ? 'pending' : 'completed', // Planes personalizados requieren configuración
            'client_name' => $instagramClientName, // Nombre de Instagram detectado automáticamente
            'meta_campaign_id' => $reconciliation->activeCampaign->meta_campaign_id,
            'campaign_start_date' => $campaignStartDate, // Fecha real de inicio de campaña
            'campaign_end_date' => $campaignEndDate, // Fecha real de final de campaña
            'transaction_date' => now(),
            'notes' => $isCustomPlan 
                ? 'Transacción pendiente - Requiere configuración de ganancia' 
                : 'Transacción automática por conciliación de campaña - Consolidada (Ingreso, Gasto, Ganancia)',
            'metadata' => [
                'plan_name' => $plan->plan_name,
                'daily_budget' => $plan->daily_budget,
                'duration_days' => $plan->duration_days,
                'is_custom_plan' => $isCustomPlan,
                'reconciliation_id' => $reconciliation->id,
                'created_via' => 'automatic_reconciliation',
                'instagram_detected' => $instagramClientName !== 'Cliente Sin Identificar',
                'campaign_dates' => [
                    'start_date' => $campaignStartDate,
                    'end_date' => $campaignEndDate,
                    'duration_days' => $reconciliation->activeCampaign->getCampaignDurationDays()
                ]
            ]
        ]);

        Log::info("Transacción contable consolidada creada para conciliación {$reconciliation->id}", [
            'plan' => $plan->plan_name,
            'campaign' => $reconciliation->activeCampaign->meta_campaign_name,
            'instagram_client' => $instagramClientName,
            'income' => $plan->client_price,
            'expense' => $plan->total_budget,
            'profit' => $plan->profit_margin,
            'is_custom_plan' => $isCustomPlan
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