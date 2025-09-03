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
            // 1. OBTENER CAMPAÑAS ACTIVAS
            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time,objective,created_time&limit=250&access_token={$facebookAccount->access_token}";
            $campaignsResponse = file_get_contents($campaignsUrl);
            $campaignsData = json_decode($campaignsResponse, true);
            
            if (!isset($campaignsData['data'])) {
                return collect();
            }
            
            $allRecords = collect();
            
            foreach ($campaignsData['data'] as $campaignData) {
                if ($campaignData['status'] === 'ACTIVE') {
                    // 2. OBTENER ADSETS DE CADA CAMPAÑA
                    $adsetsUrl = "https://graph.facebook.com/v18.0/{$campaignData['id']}/adsets?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time&limit=250&access_token={$facebookAccount->access_token}";
                    $adsetsResponse = file_get_contents($adsetsUrl);
                    $adsetsData = json_decode($adsetsResponse, true);
                    
                    if (isset($adsetsData['data'])) {
                        foreach ($adsetsData['data'] as $adsetData) {
                            if ($adsetData['status'] === 'ACTIVE') {
                                // 3. OBTENER ANUNCIOS DE CADA ADSET
                                $adsUrl = "https://graph.facebook.com/v18.0/{$adsetData['id']}/ads?fields=id,name,status,creative&limit=250&access_token={$facebookAccount->access_token}";
                                $adsResponse = file_get_contents($adsUrl);
                                $adsData = json_decode($adsResponse, true);
                                
                                if (isset($adsData['data'])) {
                                    foreach ($adsData['data'] as $adData) {
                                        if ($adData['status'] === 'ACTIVE') {
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
                                            
                                            // Datos JSON completos
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
}
