<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number', // Renamed from phone
        'client_name', // Renamed from name
        'intent', // Renamed from intention
        'lead_level', // New
        'stage',
        'confidence_score', // Renamed from confidence
        'bot_disabled', // Si es true, el bot no responderá (intervención humana)
    ];

    protected $casts = [
        'bot_disabled' => 'boolean',
        'confidence_score' => 'decimal:2',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
