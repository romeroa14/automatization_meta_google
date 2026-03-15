<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'number_phone_id',
        'message_id',
        'message_text',
        'response',
        'resource',
        'timestamp',
        'platform',
        'status',
        'message_length',
        'is_employee',
        'is_client_message',
        'lead_intent',
        'lead_level',
        'conversation_summary',
        'message_sentiment',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_employee' => 'boolean',
        'is_client_message' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
