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
        'app_id',
        'app_secret',
        'access_token',
        'token_expires_at',
        'is_active',
        'is_oauth_primary',
        'settings',
        'selected_ad_account_id',
        'selected_page_id',
        'selected_campaign_ids',
        'selected_ad_ids'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_oauth_primary' => 'boolean',
        'token_expires_at' => 'datetime',
        'settings' => 'array',
        'selected_campaign_ids' => 'array',
        'selected_ad_ids' => 'array',
    ];

    protected $rules = [
        'account_name' => 'required|string|max:255',
        'app_id' => 'required|string|max:255',
        'app_secret' => 'required|string|max:255',
        'access_token' => 'required|string|max:255',
        'token_expires_at' => 'required|date',
        'is_active' => 'required|boolean',
        'is_oauth_primary' => 'required|boolean',
        'settings' => 'required|array',
        'selected_ad_account_id' => 'nullable|string|max:255',
        'selected_page_id' => 'nullable|string|max:255',
        'selected_campaign_ids' => 'nullable|array',
        'selected_ad_ids' => 'nullable|array',
    ];

    protected $hidden = [
        // Removemos app_secret y access_token para que aparezcan en el formulario
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
               !empty($this->access_token);
    }

    public function getFullNameAttribute(): string
    {
        return $this->account_name;
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

    /**
     * Obtener la cuenta principal para OAuth
     * Prioriza la marcada como is_oauth_primary
     */
    public static function getOAuthAccount(): ?self
    {
        // Primero buscar la marcada explícitamente como principal
        $primary = static::where('is_active', true)
            ->where('is_oauth_primary', true)
            ->whereNotNull('app_id')
            ->whereNotNull('app_secret')
            ->first();
            
        if ($primary) {
            return $primary;
        }

        // Fallback: la primera activa con credenciales (comportamiento anterior)
        return static::where('is_active', true)
            ->whereNotNull('app_id')
            ->whereNotNull('app_secret')
            ->first();
    }

    /**
     * Verificar si hay credenciales OAuth configuradas
     */
    public static function hasOAuthCredentials(): bool
    {
        return static::getOAuthAccount() !== null;
    }

    /**
     * Obtener App ID para OAuth
     */
    public static function getOAuthAppId(): ?string
    {
        return static::getOAuthAccount()?->app_id;
    }

    /**
     * Obtener App Secret para OAuth
     */
    public static function getOAuthAppSecret(): ?string
    {
        return static::getOAuthAccount()?->app_secret;
    }
}
