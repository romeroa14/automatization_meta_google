<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappInstance extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'phone_number',
        'phone_number_id',
        'waba_id',
        'access_token',
        'webhook_verify_token',
        'is_default',
        'status',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
