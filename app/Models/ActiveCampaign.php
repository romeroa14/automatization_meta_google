<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ActiveCampaign extends Model
{
    // Usar la tabla de vista
    protected $table = 'active_campaigns_view';
    
    // Campos que se pueden llenar dinámicamente
    protected $fillable = [
        'meta_campaign_id',
        'meta_adset_id',
        'meta_ad_id',
        'meta_campaign_name',
        'meta_adset_name',
        'meta_ad_name',
        'campaign_daily_budget',
        'campaign_total_budget',
        'campaign_remaining_budget',
        'adset_daily_budget',
        'adset_lifetime_budget',
        'amount_spent',
        'campaign_status',
        'adset_status',
        'ad_status',
        'campaign_objective',
        'facebook_account_id',
        'ad_account_id',
        'campaign_start_time',
        'campaign_stop_time',
        'campaign_created_time',
        'adset_start_time',
        'adset_stop_time',
        'campaign_data',
        'adset_data',
        'ad_data',
    ];
    
    // Casts para los campos
    protected $casts = [
        'campaign_daily_budget' => 'decimal:2',
        'campaign_total_budget' => 'decimal:2',
        'campaign_remaining_budget' => 'decimal:2',
        'adset_daily_budget' => 'decimal:2',
        'adset_lifetime_budget' => 'decimal:2',
        'amount_spent' => 'decimal:2',
        'campaign_data' => 'array',
        'adset_data' => 'array',
        'ad_data' => 'array',
        'campaign_start_time' => 'datetime',
        'campaign_stop_time' => 'datetime',
        'campaign_created_time' => 'datetime',
        'adset_start_time' => 'datetime',
        'adset_stop_time' => 'datetime',
    ];
    
    /**
     * Relación con la cuenta de Facebook
     */
    public function facebookAccount()
    {
        return $this->belongsTo(FacebookAccount::class);
    }
    
    /**
     * Obtener campañas activas con todos los niveles jerárquicos
     */
    public static function getActiveCampaignsHierarchy($facebookAccountId, $adAccountId)
    {
        $facebookAccount = FacebookAccount::find($facebookAccountId);
        if (!$facebookAccount || !$facebookAccount->access_token) {
            return collect();
        }
        
        try {
            // 1. OBTENER CAMPAÑAS ACTIVAS (sin amount_spent porque no está disponible)
            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time,objective,created_time&limit=250&access_token={$facebookAccount->access_token}";
            
            // Log para debugging
            Log::info("Obteniendo campañas para cuenta publicitaria: {$adAccountId}", [
                'url' => $campaignsUrl
            ]);
            
            $campaignsResponse = self::makeHttpRequest($campaignsUrl);
            
            if ($campaignsResponse === false) {
                Log::error("Error obteniendo campañas para cuenta publicitaria: {$adAccountId}", [
                    'url' => $campaignsUrl,
                    'error' => 'HTTP request failed'
                ]);
                return collect();
            }
            
            $campaignsData = json_decode($campaignsResponse, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error decodificando JSON de campañas para cuenta publicitaria: {$adAccountId}", [
                    'response' => $campaignsResponse,
                    'json_error' => json_last_error_msg()
                ]);
                return collect();
            }
            
            Log::info("Campañas obtenidas de API", [
                'ad_account_id' => $adAccountId,
                'total_campaigns' => count($campaignsData['data'] ?? [])
            ]);
            
            if (!isset($campaignsData['data'])) {
                Log::warning("No se encontraron datos de campañas en la respuesta", [
                    'ad_account_id' => $adAccountId,
                    'response' => $campaignsData
                ]);
                return collect();
            }
            
            Log::info("Procesando campañas", [
                'ad_account_id' => $adAccountId,
                'total_campaigns' => count($campaignsData['data'])
            ]);
            
            $allRecords = collect();
            
            $processedCount = 0;
            $skippedCount = 0;
            
            foreach ($campaignsData['data'] as $campaignData) {
                // Incluir campañas activas y también campañas recientes (últimos 2 años)
                $isActive = $campaignData['status'] === 'ACTIVE';
                $isRecent = false;
                
                if (isset($campaignData['start_time'])) {
                    $startTime = \Carbon\Carbon::parse($campaignData['start_time']);
                    $isRecent = $startTime->isAfter(now()->subYears(2));
                }
                
                if ($isActive || $isRecent) {
                    $processedCount++;
                    Log::info("Procesando campaña: {$campaignData['name']}", [
                        'campaign_id' => $campaignData['id'],
                        'status' => $campaignData['status'],
                        'is_active' => $isActive,
                        'is_recent' => $isRecent
                    ]);
                    // 1.1. OBTENER GASTOS REALES DE LA CAMPAÑA USANDO INSIGHTS
                    $campaignSpend = 0;
                    try {
                        $insightsUrl = "https://graph.facebook.com/v18.0/{$campaignData['id']}/insights?fields=spend&time_range[since]=" . urlencode(now()->subDays(30)->format('Y-m-d')) . "&time_range[until]=" . urlencode(now()->format('Y-m-d')) . "&access_token={$facebookAccount->access_token}";
                        $insightsResponse = self::makeHttpRequest($insightsUrl);
                        if ($insightsResponse) {
                            $insightsData = json_decode($insightsResponse, true);
                            if (isset($insightsData['data']) && is_array($insightsData['data'])) {
                                foreach ($insightsData['data'] as $insight) {
                                    $campaignSpend += (float)($insight['spend'] ?? 0);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Si falla, continuar con 0
                        $campaignSpend = 0;
                    }

                    // 2. OBTENER ADSETS DE CADA CAMPAÑA
                    $adsetsUrl = "https://graph.facebook.com/v18.0/{$campaignData['id']}/adsets?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time&limit=250&access_token={$facebookAccount->access_token}";
                    
                    // Log para debugging
                    Log::info("Obteniendo adsets para campaña: {$campaignData['id']}", [
                        'campaign_name' => $campaignData['name'],
                        'url' => $adsetsUrl
                    ]);
                    
                    $adsetsResponse = self::makeHttpRequest($adsetsUrl);
                    
                    if ($adsetsResponse === false) {
                        Log::warning("Error obteniendo adsets para campaña: {$campaignData['id']}", [
                            'campaign_name' => $campaignData['name'],
                            'url' => $adsetsUrl,
                            'error' => 'HTTP request failed'
                        ]);
                        // Crear registro de campaña sin adsets
                        self::createCampaignRecord($campaignData, $campaignSpend, $facebookAccountId, $adAccountId, $allRecords);
                        continue;
                    } else {
                        $adsetsData = json_decode($adsetsResponse, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::warning("Error decodificando JSON de adsets para campaña: {$campaignData['id']}", [
                                'campaign_name' => $campaignData['name'],
                                'response' => $adsetsResponse,
                                'json_error' => json_last_error_msg()
                            ]);
                            // Crear registro de campaña sin adsets
                            self::createCampaignRecord($campaignData, $campaignSpend, $facebookAccountId, $adAccountId, $allRecords);
                            continue;
                        }
                    }
                    
                    if (isset($adsetsData['data']) && !empty($adsetsData['data'])) {
                        foreach ($adsetsData['data'] as $adsetData) {
                            // Incluir adsets activos y también recientes
                            $isAdsetActive = $adsetData['status'] === 'ACTIVE';
                            $isAdsetRecent = false;
                            
                            if (isset($adsetData['start_time'])) {
                                $adsetStartTime = \Carbon\Carbon::parse($adsetData['start_time']);
                                $isAdsetRecent = $adsetStartTime->isAfter(now()->subYears(2));
                            }
                            
                            if ($isAdsetActive || $isAdsetRecent) {
                            // 3. OBTENER ANUNCIOS DE CADA ADSET
                            $adsUrl = "https://graph.facebook.com/v18.0/{$adsetData['id']}/ads?fields=id,name,status,creative&limit=250&access_token={$facebookAccount->access_token}";
                            
                            // Log para debugging
                            Log::info("Obteniendo anuncios para adset: {$adsetData['id']}", [
                                'adset_name' => $adsetData['name'],
                                'url' => $adsUrl
                            ]);
                            
                            $adsResponse = self::makeHttpRequest($adsUrl);
                            
                            if ($adsResponse === false) {
                                Log::warning("Error obteniendo anuncios para adset: {$adsetData['id']}", [
                                    'adset_name' => $adsetData['name'],
                                    'url' => $adsUrl,
                                    'error' => 'HTTP request failed'
                                ]);
                                // Continuar sin anuncios para este adset
                                $adsData = ['data' => []];
                            } else {
                                $adsData = json_decode($adsResponse, true);
                                
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    Log::warning("Error decodificando JSON de anuncios para adset: {$adsetData['id']}", [
                                        'adset_name' => $adsetData['name'],
                                        'response' => $adsResponse,
                                        'json_error' => json_last_error_msg()
                                    ]);
                                    $adsData = ['data' => []];
                                }
                            }
                                
                                if (isset($adsData['data'])) {
                                    foreach ($adsData['data'] as $adData) {
                                        // Incluir anuncios activos y también recientes
                                        $isAdActive = $adData['status'] === 'ACTIVE';
                                        // Para anuncios, usamos la fecha de la campaña o adset
                                        $isAdRecent = $isRecent || $isAdsetRecent;
                                        
                                        if ($isAdActive || $isAdRecent) {
                                            // CREAR REGISTRO COMPLETO CON TODOS LOS NIVELES
                                            $record = new self();
                                            
                                            // IDs de niveles
                                            $record->meta_campaign_id = $campaignData['id'];
                                            $record->meta_adset_id = $adsetData['id'];
                                            $record->meta_ad_id = $adData['id'];
                                            
                                            // Nombres
                                            $record->meta_campaign_name = $campaignData['name'];
                                            $record->meta_adset_name = $adsetData['name'];
                                            $record->meta_ad_name = $adData['name'];
                                            
                                            // Presupuestos de campaña (usar método convertMetaNumber)
                                            if (isset($campaignData['daily_budget'])) {
                                                $record->campaign_daily_budget = (new self())->convertMetaNumber($campaignData['daily_budget'], 'budget');
                                            }
                                            
                                            if (isset($campaignData['lifetime_budget'])) {
                                                $record->campaign_total_budget = (new self())->convertMetaNumber($campaignData['lifetime_budget'], 'budget');
                                            }
                                            
                                            // Presupuestos de adset (usar método convertMetaNumber)
                                            if (isset($adsetData['daily_budget'])) {
                                                $record->adset_daily_budget = (new self())->convertMetaNumber($adsetData['daily_budget'], 'budget');
                                            }
                                            
                                            if (isset($adsetData['lifetime_budget'])) {
                                                $record->adset_lifetime_budget = (new self())->convertMetaNumber($adsetData['lifetime_budget'], 'budget');
                                            }
                                            
                                            // LÓGICA INTELIGENTE: Calcular presupuestos desde diferentes fuentes
                                            $duration = $record->getCampaignDurationDays();
                                            
                                            // 1. Si no hay presupuesto diario pero hay presupuesto total, calcular diario
                                            if (!$record->campaign_daily_budget && $record->campaign_total_budget && $duration > 0) {
                                                $record->campaign_daily_budget = $record->campaign_total_budget / $duration;
                                            }
                                            
                                            // 2. Si no hay presupuesto total pero hay diario, calcular total
                                            if (!$record->campaign_total_budget && $record->campaign_daily_budget && $duration > 0) {
                                                $record->campaign_total_budget = $record->campaign_daily_budget * $duration;
                                            }
                                            
                                            // 3. Si no hay presupuestos pero hay gastos, estimar desde gastos
                                            if (!$record->campaign_daily_budget && !$record->campaign_total_budget && $campaignSpend > 0 && $duration > 0) {
                                                // Estimar presupuesto diario basado en gastos y duración
                                                $estimatedDailyBudget = $campaignSpend / $duration;
                                                $record->campaign_daily_budget = $estimatedDailyBudget;
                                                $record->campaign_total_budget = $campaignSpend; // Asumir que se gastó todo
                                                
                                                // Log para debugging
                                                Log::info("Estimando presupuestos desde gastos", [
                                                    'campaign_name' => $record->meta_campaign_name,
                                                    'campaign_spend' => $campaignSpend,
                                                    'duration' => $duration,
                                                    'estimated_daily_budget' => $estimatedDailyBudget,
                                                    'estimated_total_budget' => $campaignSpend
                                                ]);
                                            }
                                            
                                            // Estados
                                            $record->campaign_status = $campaignData['status'];
                                            $record->adset_status = $adsetData['status'];
                                            $record->ad_status = $adData['status'];
                                            
                                            // Objetivo
                                            $record->campaign_objective = $campaignData['objective'] ?? null;
                                            
                                            // Fechas
                                            if (isset($campaignData['start_time'])) {
                                                $record->campaign_start_time = \Carbon\Carbon::parse($campaignData['start_time']);
                                            }
                                            
                                            if (isset($campaignData['stop_time'])) {
                                                $record->campaign_stop_time = \Carbon\Carbon::parse($campaignData['stop_time']);
                                            }
                                            
                                            if (isset($adsetData['start_time'])) {
                                                $record->adset_start_time = \Carbon\Carbon::parse($adsetData['start_time']);
                                            }
                                            
                                            if (isset($adsetData['stop_time'])) {
                                                $record->adset_stop_time = \Carbon\Carbon::parse($adsetData['stop_time']);
                                            }
                                            
                                            // Asignar gasto real al campo del modelo
                                            $record->amount_spent = $campaignSpend;
                                            
                                            // Datos JSON completos (incluir gasto real de insights)
                                            $campaignData['amount_spent'] = $campaignSpend; // Agregar gasto real obtenido de insights
                                            $record->campaign_data = $campaignData;
                                            $record->adset_data = $adsetData;
                                            $record->ad_data = $adData;
                                            
                                            // LÓGICA INTELIGENTE: Calcular presupuestos desde diferentes fuentes
                                            $duration = $record->getCampaignDurationDays();
                                            
                                            // 1. Si no hay presupuesto diario de campaña pero hay de adset, usar el de adset
                                            if (!$record->campaign_daily_budget && $record->adset_daily_budget) {
                                                $record->campaign_daily_budget = $record->adset_daily_budget;
                                            }
                                            
                                            // 2. Si no hay presupuesto total pero hay diario, calcular total
                                            if (!$record->campaign_total_budget && $record->campaign_daily_budget && $duration > 0) {
                                                $record->campaign_total_budget = $record->campaign_daily_budget * $duration;
                                            }
                                            
                                            // 3. Calcular presupuesto restante
                                            if ($record->campaign_total_budget > 0) {
                                                $record->campaign_remaining_budget = $record->campaign_total_budget - $campaignSpend;
                                                // Asegurar que no sea negativo
                                                if ($record->campaign_remaining_budget < 0) {
                                                    $record->campaign_remaining_budget = 0;
                                                }
                                            } else {
                                                $record->campaign_remaining_budget = 0;
                                            }
                                            
                                            // Relaciones
                                            $record->facebook_account_id = $facebookAccountId;
                                            $record->ad_account_id = $adAccountId;
                                            
                                            $allRecords->push($record);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            Log::info("Procesamiento de campañas completado", [
                'ad_account_id' => $adAccountId,
                'total_campaigns_from_api' => count($campaignsData['data']),
                'campaigns_processed' => $processedCount,
                'campaigns_skipped' => $skippedCount,
                'total_records_returned' => $allRecords->count()
            ]);
            
            Log::info("Método getActiveCampaignsHierarchy completado", [
                'ad_account_id' => $adAccountId,
                'total_records_returned' => $allRecords->count()
            ]);
            
            return $allRecords;
            
        } catch (\Exception $e) {
            Log::error('Error en getActiveCampaignsHierarchy: ' . $e->getMessage());
            return collect();
        }
    }
    
    /**
     * Crear registro de campaña sin adsets/ads
     */
    private static function createCampaignRecord($campaignData, $campaignSpend, $facebookAccountId, $adAccountId, &$allRecords)
    {
        $record = new self();
        $record->meta_campaign_id = $campaignData['id'];
        $record->meta_campaign_name = $campaignData['name'];
        $record->campaign_status = $campaignData['status'];
        $record->campaign_daily_budget = (new self())->convertMetaNumber($campaignData['daily_budget'] ?? 0, 'budget');
        $record->campaign_total_budget = (new self())->convertMetaNumber($campaignData['lifetime_budget'] ?? 0, 'budget');
        $record->campaign_start_time = isset($campaignData['start_time']) ? \Carbon\Carbon::parse($campaignData['start_time']) : null;
        $record->campaign_stop_time = isset($campaignData['stop_time']) ? \Carbon\Carbon::parse($campaignData['stop_time']) : null;
        $record->amount_spent = $campaignSpend;
        
        // LÓGICA INTELIGENTE: Calcular presupuestos desde diferentes fuentes
        $duration = $record->getCampaignDurationDays();
        
        // 1. Si no hay presupuesto diario pero hay presupuesto total, calcular diario
        if (!$record->campaign_daily_budget && $record->campaign_total_budget && $duration > 0) {
            $record->campaign_daily_budget = $record->campaign_total_budget / $duration;
        }
        
        // 2. Si no hay presupuesto total pero hay diario, calcular total
        if (!$record->campaign_total_budget && $record->campaign_daily_budget && $duration > 0) {
            $record->campaign_total_budget = $record->campaign_daily_budget * $duration;
        }
        
        // 3. Si no hay presupuestos pero hay gastos, estimar desde gastos
        if (!$record->campaign_daily_budget && !$record->campaign_total_budget && $campaignSpend > 0 && $duration > 0) {
            // Estimar presupuesto diario basado en gastos y duración
            $estimatedDailyBudget = $campaignSpend / $duration;
            $record->campaign_daily_budget = $estimatedDailyBudget;
            $record->campaign_total_budget = $campaignSpend; // Asumir que se gastó todo
            
            // Log para debugging
            Log::info("Estimando presupuestos desde gastos (createCampaignRecord)", [
                'campaign_name' => $record->meta_campaign_name,
                'campaign_spend' => $campaignSpend,
                'duration' => $duration,
                'estimated_daily_budget' => $estimatedDailyBudget,
                'estimated_total_budget' => $campaignSpend
            ]);
        }
        
        // Calcular presupuesto restante
        if ($record->campaign_total_budget > 0) {
            $record->campaign_remaining_budget = $record->campaign_total_budget - $campaignSpend;
            // Asegurar que no sea negativo
            if ($record->campaign_remaining_budget < 0) {
                $record->campaign_remaining_budget = 0;
            }
        } else {
            $record->campaign_remaining_budget = 0;
        }
        
        $record->campaign_objective = $campaignData['objective'] ?? null;
        $record->campaign_created_time = isset($campaignData['created_time']) ? \Carbon\Carbon::parse($campaignData['created_time']) : null;
        
        // Datos de campaña
        $campaignData['amount_spent'] = $campaignSpend;
        $record->campaign_data = $campaignData;
        $record->adset_data = [];
        $record->ad_data = [];
        
        // Relaciones
        $record->facebook_account_id = $facebookAccountId;
        $record->ad_account_id = $adAccountId;
        
        $allRecords->push($record);
        
        Log::info("Registro de campaña creado sin adsets: {$campaignData['name']}", [
            'campaign_id' => $campaignData['id'],
            'status' => $campaignData['status']
        ]);
    }
    
    /**
     * Calcular duración de la campaña
     */
    public function getCampaignDurationDays()
    {
        if ($this->campaign_start_time && $this->campaign_stop_time) {
            return $this->campaign_start_time->diffInDays($this->campaign_stop_time) + 1;
        }
        return null;
    }

    /**
     * Obtiene el estado real de la campaña basado en fechas y estado de Meta
     */
    public function getRealCampaignStatus()
    {
        if (!$this->campaign_data) {
            return 'UNKNOWN';
        }

        $metaStatus = $this->campaign_data['status'] ?? 'UNKNOWN';
        $startTime = $this->campaign_data['start_time'] ?? null;
        $stopTime = $this->campaign_data['stop_time'] ?? null;

        if (!$startTime || !$stopTime) {
            return $metaStatus;
        }

        $now = new \DateTime();
        $start = new \DateTime($startTime);
        $stop = new \DateTime($stopTime);

        // Lógica de estado real
        if ($now < $start) {
            return 'SCHEDULED'; // Aún no ha empezado
        } elseif ($now > $stop) {
            return 'COMPLETED'; // Ya terminó
        } elseif ($metaStatus === 'ACTIVE') {
            return 'ACTIVE'; // Está en rango y Meta dice activa
        } else {
            return $metaStatus; // Usar el estado de Meta
        }
    }

    /**
     * Obtiene el estado real del adset basado en fechas y estado de Meta
     */
    public function getRealAdsetStatus()
    {
        if (!$this->adset_data) {
            return 'UNKNOWN';
        }

        $metaStatus = $this->adset_data['status'] ?? 'UNKNOWN';
        $startTime = $this->adset_data['start_time'] ?? null;
        $stopTime = $this->adset_data['stop_time'] ?? null;

        if (!$startTime || !$stopTime) {
            return $metaStatus;
        }

        $now = new \DateTime();
        $start = new \DateTime($startTime);
        $stop = new \DateTime($stopTime);

        // Lógica de estado real
        if ($now < $start) {
            return 'SCHEDULED';
        } elseif ($now > $stop) {
            return 'COMPLETED';
        } elseif ($metaStatus === 'ACTIVE') {
            return 'ACTIVE';
        } else {
            return $metaStatus;
        }
    }
    
    /**
     * Calcular duración del adset
     */
    public function getAdsetDurationDays()
    {
        if ($this->adset_start_time && $this->adset_stop_time) {
            return $this->adset_start_time->diffInDays($this->adset_stop_time) + 1;
        }
        return null;
    }
    
    /**
     * Obtener presupuesto restante estimado de campaña
     */
    public function getCampaignRemainingBudget()
    {
        if ($this->campaign_total_budget && $this->campaign_daily_budget) {
            $duration = $this->getCampaignDurationDays();
            if ($duration) {
                $totalPlanned = $this->campaign_daily_budget * $duration;
                return max(0, $this->campaign_total_budget - $totalPlanned);
            }
        }
        return $this->campaign_total_budget;
    }
    
    /**
     * Obtener presupuesto restante estimado del adset
     */
    public function getAdsetRemainingBudget()
    {
        if ($this->adset_lifetime_budget && $this->adset_daily_budget) {
            $duration = $this->getAdsetDurationDays();
            if ($duration) {
                $totalPlanned = $this->adset_daily_budget * $duration;
                return max(0, $this->adset_lifetime_budget - $totalPlanned);
            }
        }
        return $this->adset_lifetime_budget;
    }
    
    /**
     * Detectar el nivel de presupuesto (campaign, adset, unknown)
     */
    public function getBudgetLevel(): string
    {
        $campaignDaily = $this->campaign_daily_budget ?? 0;
        $campaignLifetime = $this->campaign_total_budget ?? 0;
        $adsetDaily = $this->adset_daily_budget ?? 0;
        $adsetLifetime = $this->adset_lifetime_budget ?? 0;
        
        if ($campaignDaily > 0 || $campaignLifetime > 0) {
            return 'campaign';
        } elseif ($adsetDaily > 0 || $adsetLifetime > 0) {
            return 'adset';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Obtener el presupuesto diario según el nivel detectado
     */
    public function getEffectiveDailyBudget(): float
    {
        $level = $this->getBudgetLevel();
        
        if ($level === 'campaign') {
            return $this->campaign_daily_budget ?? 0;
        } elseif ($level === 'adset') {
            return $this->adset_daily_budget ?? 0;
        } else {
            return 0;
        }
    }
    
    /**
     * Obtener el presupuesto total según el nivel detectado
     */
    public function getEffectiveTotalBudget(): float
    {
        $level = $this->getBudgetLevel();
        $duration = $this->getEffectiveDuration();
        
        if ($level === 'campaign') {
            $lifetime = $this->campaign_total_budget ?? 0;
            $daily = $this->campaign_daily_budget ?? 0;
            
            if ($lifetime > 0) {
                return $lifetime;
            } elseif ($daily > 0 && $duration > 0) {
                return $daily * $duration;
            }
        } elseif ($level === 'adset') {
            $lifetime = $this->adset_lifetime_budget ?? 0;
            $daily = $this->adset_daily_budget ?? 0;
            
            if ($lifetime > 0) {
                return $lifetime;
            } elseif ($daily > 0 && $duration > 0) {
                return $daily * $duration;
            }
        }
        
        return 0;
    }
    
    /**
     * Obtener la duración efectiva según las fechas disponibles
     */
    public function getEffectiveDuration(): int
    {
        // Priorizar fechas de AdSet si existen
        $adsetStart = $this->adset_start_time;
        $adsetStop = $this->adset_stop_time;
        $campaignStart = $this->campaign_start_time;
        $campaignStop = $this->campaign_stop_time;
        
        $startTime = $adsetStart ?? $campaignStart;
        $stopTime = $adsetStop ?? $campaignStop;
        
        if ($startTime && $stopTime) {
            return $startTime->diffInDays($stopTime) + 1;
        } elseif ($startTime) {
            // Solo fecha de inicio, usar duración estimada
            return 7; // 7 días por defecto
        } else {
            return 0;
        }
    }
    
    /**
     * Calcular presupuesto restante
     */
    public function getRemainingBudget(): float
    {
        $totalBudget = $this->getEffectiveTotalBudget();
        $spent = $this->amount_spent ?? 0;
        
        return max(0, $totalBudget - $spent);
    }
    
    /**
     * Contar cuántos AdSets tiene esta campaña
     */
    public function getAdsetsCount()
    {
        return static::where('meta_campaign_id', $this->meta_campaign_id)
            ->whereNotNull('meta_adset_id')
            ->distinct('meta_adset_id')
            ->count('meta_adset_id');
    }
    
    /**
     * Contar cuántos Anuncios tiene esta campaña
     */
    public function getAdsCount()
    {
        return static::where('meta_campaign_id', $this->meta_campaign_id)
            ->whereNotNull('meta_ad_id')
            ->distinct('meta_ad_id')
            ->count();
    }
    
    /**
     * Obtener todos los AdSets de esta campaña
     */
    public function getAdsets()
    {
        return static::where('meta_campaign_id', $this->meta_campaign_id)
            ->whereNotNull('meta_adset_id')
            ->select('meta_adset_id', 'meta_adset_name', 'adset_daily_budget', 'adset_lifetime_budget', 
                     'adset_status', 'adset_start_time', 'adset_stop_time', 'adset_data')
            ->distinct('meta_adset_id')
            ->get();
    }
    
    /**
     * Obtener todos los Anuncios de un AdSet específico
     */
    public function getAdsByAdset($adsetId)
    {
        return static::where('meta_campaign_id', $this->meta_campaign_id)
            ->where('meta_adset_id', $adsetId)
            ->whereNotNull('meta_ad_id')
            ->get();
    }
    
    /**
     * Calcular presupuesto total estimado de campaña basado en presupuesto diario × duración
     */
    public function getCampaignTotalBudgetEstimated()
    {
        if ($this->campaign_daily_budget && $this->getCampaignDurationDays()) {
            return $this->campaign_daily_budget * $this->getCampaignDurationDays();
        }
        
        // Si no se puede calcular, usar el valor de Meta si existe
        return $this->campaign_total_budget;
    }
    
    /**
     * Calcular presupuesto total estimado del adset basado en presupuesto diario × duración
     */
    public function getAdsetTotalBudgetEstimated()
    {
        if ($this->adset_daily_budget && $this->getAdsetDurationDays()) {
            return $this->adset_daily_budget * $this->getAdsetDurationDays();
        }
        
        // Si no se puede calcular, usar el valor de Meta si existe
        return $this->adset_lifetime_budget;
    }
    
    /**
     * Obtener información de duración y presupuestos de Meta API
     */
    public function getMetaBudgetInfo()
    {
        $info = [];
        
        // Información de campaña
        if (isset($this->campaign_data)) {
            $campaignData = $this->campaign_data;
            
            $info['campaign'] = [
                'daily_budget' => $campaignData['daily_budget'] ?? null,
                'lifetime_budget' => $campaignData['lifetime_budget'] ?? null,
                'budget_remaining' => $campaignData['budget_remaining'] ?? null,
                'start_time' => $campaignData['start_time'] ?? null,
                'stop_time' => $campaignData['stop_time'] ?? null,
                'created_time' => $campaignData['created_time'] ?? null,
                'updated_time' => $campaignData['updated_time'] ?? null,
            ];
        }
        
        // Información de adset
        if (isset($this->adset_data)) {
            $adsetData = $this->adset_data;
            
            $info['adset'] = [
                'daily_budget' => $adsetData['daily_budget'] ?? null,
                'lifetime_budget' => $adsetData['lifetime_budget'] ?? null,
                'budget_remaining' => $adsetData['budget_remaining'] ?? null,
                'start_time' => $adsetData['start_time'] ?? null,
                'stop_time' => $adsetData['stop_time'] ?? null,
                'created_time' => $adsetData['created_time'] ?? null,
                'updated_time' => $adsetData['updated_time'] ?? null,
            ];
        }
        
        return $info;
    }
    
    /**
     * Obtener presupuesto restante real desde Meta API
     */
    public function getCampaignBudgetRemainingFromMeta()
    {
        if (isset($this->campaign_data['budget_remaining'])) {
            $budgetRemaining = $this->campaign_data['budget_remaining'];
            // Convertir de centavos a dólares si es necesario
            return $budgetRemaining > 1000 ? $budgetRemaining / 100 : $budgetRemaining;
        }
        
        return null;
    }
    
    /**
     * Obtener presupuesto restante real del adset desde Meta API
     */
    public function getAdsetBudgetRemainingFromMeta()
    {
        if (isset($this->adset_data['budget_remaining'])) {
            $budgetRemaining = $this->adset_data['budget_remaining'];
            // Convertir de centavos a dólares si es necesario
            return $budgetRemaining > 1000 ? $budgetRemaining / 100 : $budgetRemaining;
        }
        
        return null;
    }
    
    /**
     * Debug: Obtener información completa de presupuestos para análisis
     */
    public function getBudgetDebugInfo()
    {
        $debug = [];
        
        // Valores almacenados en la base de datos
        $debug['database'] = [
            'campaign_daily_budget' => $this->campaign_daily_budget,
            'adset_daily_budget' => $this->adset_daily_budget,
            'campaign_total_budget' => $this->campaign_total_budget,
            'adset_lifetime_budget' => $this->adset_lifetime_budget,
        ];
        
        // Valores originales de Meta API
        if (isset($this->campaign_data)) {
            $debug['meta_campaign'] = [
                'daily_budget' => $this->campaign_data['daily_budget'] ?? null,
                'lifetime_budget' => $this->campaign_data['lifetime_budget'] ?? null,
                'budget_remaining' => $this->campaign_data['budget_remaining'] ?? null,
                'amount_spent' => $this->campaign_data['amount_spent'] ?? null,
                'raw_amount_spent' => $this->campaign_data['amount_spent'] ?? null,
            ];
        }
        
        if (isset($this->adset_data)) {
            $debug['meta_adset'] = [
                'daily_budget' => $this->adset_data['daily_budget'] ?? null,
                'lifetime_budget' => $this->adset_data['lifetime_budget'] ?? null,
                'budget_remaining' => $this->adset_data['budget_remaining'] ?? null,
                'amount_spent' => $this->adset_data['amount_spent'] ?? null,
                'raw_amount_spent' => $this->adset_data['amount_spent'] ?? null,
            ];
        }
        
        // Cálculos
        $debug['calculations'] = [
            'campaign_duration_days' => $this->getCampaignDurationDays(),
            'adset_duration_days' => $this->getAdsetDurationDays(),
            'campaign_total_estimated' => $this->getCampaignTotalBudgetEstimated(),
            'adset_total_estimated' => $this->getAdsetTotalBudgetEstimated(),
        ];
        
        // Análisis de formato
        $debug['format_analysis'] = [
            'daily_budget_raw' => $this->campaign_data['daily_budget'] ?? $this->adset_data['daily_budget'] ?? null,
            'daily_budget_converted' => $this->campaign_daily_budget ?? $this->adset_daily_budget ?? null,
            'amount_spent_raw' => $this->campaign_data['amount_spent'] ?? $this->adset_data['amount_spent'] ?? null,
            'amount_spent_converted' => $this->getAmountSpentFromMeta(),
            'total_calculated' => ($this->campaign_daily_budget ?? $this->adset_daily_budget ?? 0) * ($this->getCampaignDurationDays() ?? $this->getAdsetDurationDays() ?? 0),
        ];
        
        // Detección de formato
        $debug['format_detection'] = $this->detectMetaNumberFormat();
        
        // Conversiones con nuevo método
        $debug['conversions'] = [
            'daily_budget_converted_new' => $this->convertMetaNumber($this->campaign_data['daily_budget'] ?? $this->adset_data['daily_budget'] ?? null, 'budget'),
            'amount_spent_converted_new' => $this->convertMetaNumber($this->campaign_data['amount_spent'] ?? $this->adset_data['amount_spent'] ?? null, 'amount'),
        ];
        
        return $debug;
    }
    
    /**
     * Obtener presupuesto gastado desde Meta API
     */
    public function getAmountSpentFromMeta()
    {
        // Intentar obtener desde datos de campaña
        if (isset($this->campaign_data['amount_spent'])) {
            $amountSpent = $this->campaign_data['amount_spent'];
            // Convertir de centavos a dólares si es necesario
            return $amountSpent > 1000 ? $amountSpent / 100 : $amountSpent;
        }
        
        // Intentar obtener desde datos de adset
        if (isset($this->adset_data['amount_spent'])) {
            $amountSpent = $this->adset_data['amount_spent'];
            // Convertir de centavos a dólares si es necesario
            return $amountSpent > 1000 ? $amountSpent / 100 : $amountSpent;
        }
        
        return null;
    }
    
    /**
     * Calcular presupuesto gastado estimado basado en días transcurridos
     */
    public function getAmountSpentEstimated()
    {
        $dailyBudget = $this->campaign_daily_budget ?? $this->adset_daily_budget;
        
        if (!$dailyBudget) {
            return 0;
        }
        
        // Convertir centavos a dólares si es necesario
        if ($dailyBudget >= 10) {
            $dailyBudget = $dailyBudget / 100;
        }
        
        // Calcular días transcurridos desde el inicio
        $startTime = $this->campaign_start_time ?? $this->adset_start_time;
        
        if (!$startTime) {
            return 0;
        }
        
        $now = now();
        $daysElapsed = $startTime->diffInDays($now);
        
        // Estimar gasto basado en presupuesto diario × días transcurridos
        return $dailyBudget * $daysElapsed;
    }
    
    /**
     * Detectar formato de números de Meta API
     */
    public function detectMetaNumberFormat()
    {
        $format = [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'uses_centavos' => false,
            'uses_commas_as_decimals' => false,
        ];
        
        // Verificar si usa centavos - Meta API SIEMPRE devuelve valores en centavos
        $dailyBudget = $this->campaign_data['daily_budget'] ?? $this->adset_data['daily_budget'] ?? null;
        if ($dailyBudget) {
            // Convertir a número si es string
            $budgetValue = is_string($dailyBudget) ? (float) $dailyBudget : $dailyBudget;
            
            // Si el valor es >= 10, probablemente está en centavos
            // Ejemplos: 100 = $1.00, 500 = $5.00, 1000 = $10.00
            if ($budgetValue >= 10) {
                $format['uses_centavos'] = true;
            }
        }
        
        // Verificar si usa comas como separador decimal
        $amountSpent = $this->campaign_data['amount_spent'] ?? $this->adset_data['amount_spent'] ?? null;
        if ($amountSpent && is_string($amountSpent) && strpos($amountSpent, ',') !== false) {
            $format['uses_commas_as_decimals'] = true;
            $format['decimal_separator'] = ',';
        }
        
        return $format;
    }
    
    /**
     * Convertir número de Meta API al formato correcto
     */
    public function convertMetaNumber($value, $type = 'amount')
    {
        if ($value === null) {
            return 0;
        }
        
        // Si es string, verificar formato
        if (is_string($value)) {
            // Si usa comas como separador decimal (ej: "16,03")
            if (strpos($value, ',') !== false && strpos($value, '.') === false) {
                $value = str_replace(',', '.', $value);
            }
            
            // Convertir a float
            $value = (float) $value;
        }
        
        // Si es número, verificar si está en centavos
        if (is_numeric($value)) {
            // Para presupuestos diarios y totales
            if ($type === 'budget') {
                // Meta API devuelve presupuestos en centavos
                // Ejemplos: 100 = $1.00, 500 = $5.00, 1000 = $10.00
                if ($value >= 10) {
                    $value = $value / 100;
                }
            }
            
            // Para montos gastados, verificar si está en centavos
            if ($type === 'amount') {
                // Meta API devuelve montos gastados en centavos
                // Ejemplos: 1000 = $10.00, 5000 = $50.00
                if ($value >= 100) {
                    $value = $value / 100;
                }
            }
        }
        
        return $value;
    }

    /**
     * Hacer llamada HTTP con cURL (más confiable que file_get_contents)
     */
    public static function makeHttpRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; ADMETRICAS/1.0)');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error("cURL Error: {$error}", ['url' => $url]);
            return false;
        }
        
        if ($httpCode !== 200) {
            Log::error("HTTP Error: {$httpCode}", ['url' => $url, 'response' => $response]);
            return false;
        }
        
        return $response;
    }
    
    /**
     * Analizar stop_time de todos los AdSets de una campaña
     * Retorna información sobre la consistencia de fechas
     */
    public function analyzeAdsetsStopTimes()
    {
        // Obtener todos los AdSets de esta campaña
        $adsets = $this->getAdsets();
        
        if ($adsets->isEmpty()) {
            return [
                'status' => 'no_adsets',
                'message' => 'No se encontraron AdSets para esta campaña',
                'duration_days' => null,
                'stop_time' => null,
                'adsets_count' => 0
            ];
        }
        
        $stopTimes = [];
        $startTimes = [];
        
        foreach ($adsets as $adset) {
            // Recopilar stop_times
            if (isset($adset->adset_data['stop_time']) && $adset->adset_data['stop_time']) {
                $stopTimes[] = $adset->adset_data['stop_time'];
            }
            
            // Recopilar start_times para referencia
            if (isset($adset->adset_data['start_time']) && $adset->adset_data['start_time']) {
                $startTimes[] = $adset->adset_data['start_time'];
            }
        }
        
        // Si no hay stop_times en ningún AdSet, verificar si la campaña tiene stop_time
        if (empty($stopTimes)) {
            // Verificar si la campaña tiene stop_time como fallback
            $campaignStopTime = $this->campaign_data['stop_time'] ?? null;
            $campaignStartTime = $this->campaign_data['start_time'] ?? null;
            
            if ($campaignStopTime) {
                // Calcular duración basada en stop_time de la campaña
                $durationDays = null;
                if ($campaignStartTime) {
                    try {
                        $start = \Carbon\Carbon::parse($campaignStartTime);
                        $stop = \Carbon\Carbon::parse($campaignStopTime);
                        
                        // Para campañas publicitarias, contar días de ejecución (inclusive)
                        $durationDays = $start->diffInDays($stop) + 1;
                    } catch (\Exception $e) {
                        Log::error("Error calculando duración desde campaña: " . $e->getMessage());
                    }
                }
                
                return [
                    'status' => 'campaign_fallback',
                    'message' => 'Usando fecha de finalización de la campaña (AdSets sin stop_time)',
                    'duration_days' => $durationDays,
                    'stop_time' => $campaignStopTime,
                    'start_time' => $campaignStartTime,
                    'adsets_count' => $adsets->count(),
                    'source' => 'campaign'
                ];
            }
            
            // Si no hay stop_times, calcular duración basada en el nombre de la campaña
            $defaultDuration = $this->calculateDurationFromName();
            
            return [
                'status' => 'no_stop_times',
                'message' => 'Ningún AdSet ni la campaña tienen fecha de finalización configurada. Calculando duración del nombre.',
                'duration_days' => $defaultDuration,
                'stop_time' => null,
                'adsets_count' => $adsets->count(),
                'start_times' => $startTimes,
                'source' => 'name_calculation'
            ];
        }
        
        // Verificar si todos los stop_times son iguales
        $uniqueStopTimes = array_unique($stopTimes);
        
        if (count($uniqueStopTimes) === 1) {
            // Todos los AdSets tienen la misma fecha de finalización
            $stopTime = $uniqueStopTimes[0];
            $startTime = !empty($startTimes) ? $startTimes[0] : null;
            
            // Calcular duración
            $durationDays = null;
            if ($startTime && $stopTime) {
                try {
                    $start = \Carbon\Carbon::parse($startTime);
                    $stop = \Carbon\Carbon::parse($stopTime);
                    
                    // Para campañas publicitarias, contar días de ejecución (inclusive)
                    // Si empieza el 19 y termina el 23, son 5 días de ejecución
                    $durationDays = $start->diffInDays($stop) + 1;
                } catch (\Exception $e) {
                    Log::error("Error calculando duración: " . $e->getMessage());
                }
            }
            
            return [
                'status' => 'consistent',
                'message' => 'Todos los AdSets tienen la misma fecha de finalización',
                'duration_days' => $durationDays,
                'stop_time' => $stopTime,
                'start_time' => $startTime,
                'adsets_count' => $adsets->count(),
                'stop_times' => $stopTimes
            ];
        } else {
            // Los AdSets tienen fechas de finalización diferentes
            return [
                'status' => 'inconsistent',
                'message' => 'Los AdSets tienen fechas de finalización diferentes',
                'duration_days' => null,
                'stop_time' => null,
                'adsets_count' => $adsets->count(),
                'stop_times' => $stopTimes,
                'unique_stop_times' => $uniqueStopTimes
            ];
        }
    }
    
    /**
     * Obtener duración de campaña basada en análisis de AdSets
     */
    public function getCampaignDurationFromAdsets()
    {
        $analysis = $this->analyzeAdsetsStopTimes();
        
        if (in_array($analysis['status'], ['consistent', 'campaign_fallback', 'no_stop_times'])) {
            return $analysis['duration_days'];
        }
        
        return null;
    }
    
    /**
     * Obtener presupuesto total basado en análisis de AdSets
     */
    public function getCampaignTotalBudgetFromAdsets()
    {
        $analysis = $this->analyzeAdsetsStopTimes();
        
        if (in_array($analysis['status'], ['consistent', 'campaign_fallback', 'no_stop_times']) && $analysis['duration_days']) {
            $dailyBudget = $this->campaign_daily_budget ?? $this->adset_daily_budget;
            if ($dailyBudget) {
                return $dailyBudget * $analysis['duration_days'];
            }
        }
        
        return null;
    }
    
    /**
     * Obtener presupuesto restante basado en análisis de AdSets
     */
    public function getCampaignRemainingBudgetFromAdsets()
    {
        $analysis = $this->analyzeAdsetsStopTimes();
        
        if (in_array($analysis['status'], ['consistent', 'campaign_fallback', 'no_stop_times']) && $analysis['duration_days']) {
            $dailyBudget = $this->campaign_daily_budget ?? $this->adset_daily_budget;
            if ($dailyBudget) {
                $totalBudget = $dailyBudget * $analysis['duration_days'];
                
                // Usar override si existe
                $override = $this->campaign_data['amount_spent_override'] ?? null;
                if ($override !== null) {
                    return max(0, $totalBudget - (float) $override);
                }
                
                // Usar gastado normal
                $spent = $this->amount_spent ?? 0;
                return max(0, $totalBudget - $spent);
            }
        }
        
        return null;
    }
    
    /**
     * Calcular duración basada en el nombre de la campaña
     * Busca patrones como "19/09 - 23/09" en el nombre
     */
    public function calculateDurationFromName()
    {
        $campaignName = $this->meta_campaign_name ?? '';
        
        // Buscar patrón de fechas en el nombre (ej: "19/09 - 23/09")
        if (preg_match('/(\d{1,2}\/\d{1,2})\s*-\s*(\d{1,2}\/\d{1,2})/', $campaignName, $matches)) {
            try {
                $startDate = \Carbon\Carbon::createFromFormat('d/m', $matches[1]);
                $endDate = \Carbon\Carbon::createFromFormat('d/m', $matches[2]);
                
                // Calcular duración (inclusive)
                $duration = $startDate->diffInDays($endDate) + 1;
                
                return $duration;
            } catch (\Exception $e) {
                // Si hay error, usar duración por defecto
            }
        }
        
        // Si no se puede calcular, usar duración por defecto
        return 5; // 5 días por defecto
    }
    
}
