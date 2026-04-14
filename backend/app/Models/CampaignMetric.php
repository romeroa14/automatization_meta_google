<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMetric extends Model
{
    protected $fillable = [
        'workspace_id',
        'meta_campaign_id',
        'campaign_name',
        'spend',
        'impressions',
        'clicks',
        'leads_generated',
        'date',
    ];

    protected $casts = [
        'spend' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'leads_generated' => 'integer',
        'date' => 'date',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
