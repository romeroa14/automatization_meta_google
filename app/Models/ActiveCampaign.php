<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'adset_daily_budget',
        'adset_lifetime_budget',
        'campaign_status',
        'adset_status',
        'ad_status',
        'campaign_objective',
        'facebook_account_id',
        'ad_account_id',
        'campaign_start_time',
        'campaign_stop_time',
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
        'adset_daily_budget' => 'decimal:2',
        'adset_lifetime_budget' => 'decimal:2',
        'campaign_data' => 'array',
        'adset_data' => 'array',
        'ad_data' => 'array',
        'campaign_start_time' => 'datetime',
        'campaign_stop_time' => 'datetime',
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
            $campaignsResponse = file_get_contents($campaignsUrl);
            $campaignsData = json_decode($campaignsResponse, true);
            
            if (!isset($campaignsData['data'])) {
                return collect();
            }
            
            $allRecords = collect();
            
            foreach ($campaignsData['data'] as $campaignData) {
                // Incluir campañas activas y también campañas recientes (últimos 2 años)
                $isActive = $campaignData['status'] === 'ACTIVE';
                $isRecent = false;
                
                if (isset($campaignData['start_time'])) {
                    $startTime = \Carbon\Carbon::parse($campaignData['start_time']);
                    $isRecent = $startTime->isAfter(now()->subYears(2));
                }
                
                if ($isActive || $isRecent) {
                    // 1.1. OBTENER GASTOS REALES DE LA CAMPAÑA USANDO INSIGHTS
                    $campaignSpend = 0;
                    try {
                        $insightsUrl = "https://graph.facebook.com/v18.0/{$campaignData['id']}/insights?fields=spend&time_range[since]=" . urlencode(now()->subDays(30)->format('Y-m-d')) . "&time_range[until]=" . urlencode(now()->format('Y-m-d')) . "&access_token={$facebookAccount->access_token}";
                        $insightsResponse = @file_get_contents($insightsUrl);
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
                    $adsetsResponse = file_get_contents($adsetsUrl);
                    $adsetsData = json_decode($adsetsResponse, true);
                    
                    if (isset($adsetsData['data'])) {
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
                                $adsResponse = file_get_contents($adsUrl);
                                $adsData = json_decode($adsResponse, true);
                                
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
                                            
                                            // Presupuestos de campaña
                                            if (isset($campaignData['daily_budget']) && is_numeric($campaignData['daily_budget'])) {
                                                $record->campaign_daily_budget = $campaignData['daily_budget'] > 1000 ? 
                                                    $campaignData['daily_budget'] / 100 : 
                                                    $campaignData['daily_budget'];
                                            }
                                            
                                            if (isset($campaignData['lifetime_budget']) && is_numeric($campaignData['lifetime_budget'])) {
                                                $record->campaign_total_budget = $campaignData['lifetime_budget'] > 1000 ? 
                                                    $campaignData['lifetime_budget'] / 100 : 
                                                    $campaignData['lifetime_budget'];
                                            }
                                            
                                            // Presupuestos de adset
                                            if (isset($adsetData['daily_budget']) && is_numeric($adsetData['daily_budget'])) {
                                                $record->adset_daily_budget = $adsetData['daily_budget'] > 1000 ? 
                                                    $adsetData['daily_budget'] / 100 : 
                                                    $adsetData['daily_budget'];
                                            }
                                            
                                            if (isset($adsetData['lifetime_budget']) && is_numeric($adsetData['lifetime_budget'])) {
                                                $record->adset_lifetime_budget = $adsetData['lifetime_budget'] > 1000 ? 
                                                    $adsetData['lifetime_budget'] / 100 : 
                                                    $adsetData['lifetime_budget'];
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
                                            
                                            // Datos JSON completos (incluir gasto real de insights)
                                            $campaignData['amount_spent'] = $campaignSpend; // Agregar gasto real obtenido de insights
                                            $record->campaign_data = $campaignData;
                                            $record->adset_data = $adsetData;
                                            $record->ad_data = $adData;
                                            
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
            
            return $allRecords;
            
        } catch (\Exception $e) {
            return collect();
        }
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
}
