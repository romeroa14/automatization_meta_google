<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'workspace_id',
        'whatsapp_instance_id',
        'phone_number',
        'client_name',
        'intent',
        'lead_level',
        'stage',
        'confidence_score',
        'bot_disabled',
        'last_human_intervention_at',
        'ai_classification',
    ];

    protected $casts = [
        'ai_classification' => 'array', // Para que jsonb sea interpretado como array asociativo
        'confidence_score' => 'decimal:2',
        'bot_disabled' => 'boolean',
        'last_human_intervention_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
