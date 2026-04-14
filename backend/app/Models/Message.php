<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'tenant_messages';

    protected $fillable = [
        'workspace_id',
        'whatsapp_instance_id',
        'tenant_lead_id',
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
        return $this->belongsTo(Lead::class, 'tenant_lead_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function whatsappInstance(): BelongsTo
    {
        return $this->belongsTo(WhatsappInstance::class);
    }
}
