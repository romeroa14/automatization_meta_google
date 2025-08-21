<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacebookAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_name',
        'account_id',
        'app_id',
        'app_secret',
        'access_token',
        'is_active',
        'settings',
        'selected_ad_account_id',
        'selected_campaign_ids'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'selected_campaign_ids' => 'array',
    ];

    protected $hidden = [
        'app_secret',
        'access_token',
    ];

    public function automationTasks(): HasMany
    {
        return $this->hasMany(AutomationTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getMaskedAccessTokenAttribute(): string
    {
        $token = $this->access_token;
        if (strlen($token) > 10) {
            return substr($token, 0, 10) . '...' . substr($token, -10);
        }
        return $token;
    }

    public function getMaskedAppSecretAttribute(): string
    {
        $secret = $this->app_secret;
        if (strlen($secret) > 8) {
            return substr($secret, 0, 8) . '...';
        }
        return $secret;
    }

    public function hasValidCredentials(): bool
    {
        return !empty($this->app_id) && 
               !empty($this->app_secret) && 
               !empty($this->access_token) && 
               !empty($this->account_id);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->account_name} (ID: {$this->account_id})";
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Activa' : 'Inactiva';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function canBeAutomated(): bool
    {
        return $this->is_active && $this->hasValidCredentials();
    }

    public function getActiveAutomationTasksCountAttribute(): int
    {
        return $this->automationTasks()->where('is_active', true)->count();
    }
}
