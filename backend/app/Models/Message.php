<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'message_id',
        'direction',
        'is_client_message',
        'is_employee',
        'content',
        'platform',
        'status',
        'message_length',
        'handled_by_ai',
        'timestamp',
    ];

    protected $casts = [
        'is_client_message' => 'boolean',
        'is_employee' => 'boolean',
        'handled_by_ai' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
