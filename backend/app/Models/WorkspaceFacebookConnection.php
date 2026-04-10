<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceFacebookConnection extends Model
{
    protected $fillable = [
        'workspace_id',
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
        'scopes' => 'array',
        'ad_accounts' => 'array',
        'pages' => 'array',
        'is_active' => 'boolean',
        'token_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
