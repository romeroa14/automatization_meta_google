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
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
