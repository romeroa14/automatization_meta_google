<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'lead_id',
        'message',
        'sender',
        'platform', // e.g., 'telegram', 'whatsapp'
        'payload', // JSON data if needed
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
