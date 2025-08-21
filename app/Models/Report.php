<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Report extends Model
{
    protected $fillable = [
        'name',
        'description',
        'period_start',
        'period_end',
        'selected_facebook_accounts',
        'selected_campaigns',
        'brands_config',
        'statistics_config',
        'charts_config',
        'generated_data',
        'google_slides_url',
        'status',
        'generated_at',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'selected_facebook_accounts' => 'array',
        'selected_campaigns' => 'array',
        'brands_config' => 'array',
        'statistics_config' => 'array',
        'charts_config' => 'array',
        'generated_data' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'generated_at' => 'datetime',
    ];

    // Relaciones
    public function brands(): HasMany
    {
        return $this->hasMany(ReportBrand::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(ReportCampaign::class);
    }

    public function facebookAccounts(): BelongsToMany
    {
        return $this->belongsToMany(FacebookAccount::class, 'report_facebook_accounts');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }

    // Métodos
    public function getPeriodDaysAttribute(): int
    {
        return Carbon::parse($this->period_start)->diffInDays($this->period_end) + 1;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Borrador',
            'generating' => 'Generando',
            'completed' => 'Completado',
            'failed' => 'Fallido',
            default => 'Desconocido',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'generating' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }

    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsGenerating(): void
    {
        $this->update([
            'status' => 'generating',
            'generated_at' => null,
        ]);
    }

    public function markAsCompleted(string $googleSlidesUrl = null): void
    {
        $this->update([
            'status' => 'completed',
            'google_slides_url' => $googleSlidesUrl,
            'generated_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'generated_at' => now(),
        ]);
    }

    public function getTotalCampaignsAttribute(): int
    {
        return $this->campaigns()->count();
    }

    public function getTotalBrandsAttribute(): int
    {
        return $this->brands()->count();
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
}
