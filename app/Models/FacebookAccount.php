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
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
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
}
