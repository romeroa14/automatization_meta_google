<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Workspace extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'website',
        'email',
        'phone',
        'settings',
        'plan_type',
        'n8n_webhook_url',
        'is_active',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function whatsappInstances(): HasMany
    {
        return $this->hasMany(WhatsappInstance::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function campaignMetrics(): HasMany
    {
        return $this->hasMany(CampaignMetric::class);
    }
}
