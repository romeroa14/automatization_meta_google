<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCampaign extends Model
{
    protected $fillable = [
        'report_id',
        'report_brand_id',
        'campaign_id',
        'campaign_name',
        'ad_account_id',
        'campaign_data',
        'statistics',
        'ad_image_url',
        'ad_image_local_path',
        'slide_order',
        'is_active',
    ];

    protected $casts = [
        'campaign_data' => 'array',
        'statistics' => 'array',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ReportBrand::class, 'report_brand_id');
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

    public function scopeByBrand($query, $brandId)
    {
        return $query->where('report_brand_id', $brandId);
    }

    // Métodos
    public function getReachAttribute(): int
    {
        return $this->statistics['reach'] ?? 0;
    }

    public function getImpressionsAttribute(): int
    {
        return $this->statistics['impressions'] ?? 0;
    }

    public function getClicksAttribute(): int
    {
        return $this->statistics['clicks'] ?? 0;
    }

    public function getSpendAttribute(): float
    {
        return $this->statistics['spend'] ?? 0;
    }

    public function getCTRAttribute(): float
    {
        return $this->statistics['ctr'] ?? 0;
    }

    public function getCPMAttribute(): float
    {
        return $this->statistics['cpm'] ?? 0;
    }

    public function getCPCAttribute(): float
    {
        return $this->statistics['cpc'] ?? 0;
    }

    public function getFrequencyAttribute(): float
    {
        return $this->statistics['frequency'] ?? 0;
    }

    public function getTotalInteractionsAttribute(): int
    {
        return $this->statistics['total_interactions'] ?? 0;
    }

    public function getInteractionRateAttribute(): float
    {
        return $this->statistics['interaction_rate'] ?? 0;
    }

    public function getVideoViewsAttribute(): int
    {
        return $this->statistics['video_views_p100'] ?? 0;
    }

    public function getVideoCompletionRateAttribute(): float
    {
        return $this->statistics['video_completion_rate'] ?? 0;
    }

    public function getInlineLinkClicksAttribute(): int
    {
        return $this->statistics['inline_link_clicks'] ?? 0;
    }

    public function getUniqueClicksAttribute(): int
    {
        return $this->statistics['unique_clicks'] ?? 0;
    }

    public function getAdImagePathAttribute(): string
    {
        if ($this->ad_image_local_path && file_exists(storage_path('app/public/' . $this->ad_image_local_path))) {
            return asset('storage/' . $this->ad_image_local_path);
        }
        
        return $this->ad_image_url ?? '';
    }

    public function hasLocalImage(): bool
    {
        return !empty($this->ad_image_local_path) && file_exists(storage_path('app/public/' . $this->ad_image_local_path));
    }

    public function getFormattedSpendAttribute(): string
    {
        return '$' . number_format($this->spend, 2);
    }

    public function getFormattedCTRAttribute(): string
    {
        return number_format($this->ctr, 2) . '%';
    }

    public function getFormattedCPMAttribute(): string
    {
        return '$' . number_format($this->cpm, 2);
    }

    public function getFormattedCPCAttribute(): string
    {
        return '$' . number_format($this->cpc, 2);
    }

    public function getFormattedInteractionRateAttribute(): string
    {
        return number_format($this->interaction_rate, 2) . '%';
    }

    public function getFormattedVideoCompletionRateAttribute(): string
    {
        return number_format($this->video_completion_rate, 2) . '%';
    }

    public function getFormattedFrequencyAttribute(): string
    {
        return number_format($this->frequency, 2);
    }
}
