<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportBrand extends Model
{
    protected $fillable = [
        'report_id',
        'brand_name',
        'brand_identifier',
        'campaign_ids',
        'brand_settings',
        'slide_order',
        'is_active',
    ];

    protected $casts = [
        'campaign_ids' => 'array',
        'brand_settings' => 'array',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(ReportCampaign::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('slide_order', 'asc');
    }

    // Métodos
    public function getTotalCampaignsAttribute(): int
    {
        return $this->campaigns()->count();
    }

    public function getTotalReachAttribute(): int
    {
        return $this->campaigns()->get()->sum(function($campaign) {
            return $campaign->statistics['reach'] ?? 0;
        });
    }

    public function getTotalImpressionsAttribute(): int
    {
        return $this->campaigns()->get()->sum(function($campaign) {
            return $campaign->statistics['impressions'] ?? 0;
        });
    }

    public function getTotalClicksAttribute(): int
    {
        return $this->campaigns()->get()->sum(function($campaign) {
            return $campaign->statistics['clicks'] ?? 0;
        });
    }

    public function getTotalSpendAttribute(): float
    {
        return $this->campaigns()->get()->sum(function($campaign) {
            return $campaign->statistics['spend'] ?? 0;
        });
    }

    public function getAverageCTRAttribute(): float
    {
        $totalClicks = $this->total_clicks;
        $totalImpressions = $this->total_impressions;
        
        if ($totalImpressions > 0) {
            return round(($totalClicks / $totalImpressions) * 100, 2);
        }
        
        return 0;
    }

    public function getAverageCPMAttribute(): float
    {
        $totalSpend = $this->total_spend;
        $totalImpressions = $this->total_impressions;
        
        if ($totalImpressions > 0) {
            return round(($totalSpend / $totalImpressions) * 1000, 2);
        }
        
        return 0;
    }

    public function getAverageCPCAttribute(): float
    {
        $totalSpend = $this->total_spend;
        $totalClicks = $this->total_clicks;
        
        if ($totalClicks > 0) {
            return round($totalSpend / $totalClicks, 2);
        }
        
        return 0;
    }
}
