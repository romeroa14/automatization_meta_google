<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class UserFacebookConnection extends Model
{
    protected $fillable = [
        'user_id',
        'facebook_user_id',
        'facebook_name',
        'facebook_email',
        'access_token',
        'token_expires_at',
        'scopes',
        'ad_accounts',
        'pages',
        'selected_ad_account_id',
        'selected_page_id',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'scopes' => 'array',
        'ad_accounts' => 'array',
        'pages' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Campos que NUNCA deben exponerse en JSON/API
     */
    protected $hidden = [
        'access_token',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Encriptar access_token antes de guardar
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    /**
     * Desencriptar access_token al leer
     */
    public function getAccessTokenAttribute($value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value; // Fallback si no está encriptado
        }
    }

    /**
     * Verificar si el token ha expirado
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * Verificar si el token necesita renovación (menos de 7 días)
     */
    public function needsRenewal(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }
        return $this->token_expires_at->isBefore(now()->addDays(7));
    }

    /**
     * Actualizar timestamp de último uso
     */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope para conexiones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para conexiones con token válido
     */
    public function scopeWithValidToken($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('token_expires_at')
                           ->orWhere('token_expires_at', '>', now());
                     });
    }
}
