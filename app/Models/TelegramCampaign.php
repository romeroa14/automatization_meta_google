<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramCampaign extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'telegram_conversation_id',
        'campaign_name',
        'objective',
        'budget_type',
        'daily_budget',
        'start_date',
        'end_date',
        'targeting_data',
        'ad_data',
        'media_type',
        'media_url',
        'ad_copy',
        'meta_campaign_id',
        'meta_adset_id',
        'meta_ad_id',
        'status',
        'error_message',
    ];

    protected $casts = [
        'targeting_data' => 'array',
        'ad_data' => 'array',
        'daily_budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TelegramConversation::class, 'telegram_conversation_id');
    }

    public function getObjectiveNameAttribute(): string
    {
        $objectives = [
            'TRAFFIC' => 'Tráfico',
            'CONVERSIONS' => 'Conversiones',
            'REACH' => 'Alcance',
            'BRAND_AWARENESS' => 'Conocimiento de Marca',
            'VIDEO_VIEWS' => 'Visualizaciones de Video',
            'LEAD_GENERATION' => 'Generación de Leads',
            'MESSAGES' => 'Mensajes',
            'ENGAGEMENT' => 'Interacción',
            'APP_INSTALLS' => 'Instalaciones de App',
            'STORE_VISITS' => 'Visitas a Tienda',
        ];

        return $objectives[$this->objective] ?? $this->objective;
    }

    public function getBudgetTypeNameAttribute(): string
    {
        return $this->budget_type === 'campaign_daily_budget' ? 'Campaña' : 'Conjunto de Anuncios';
    }

    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'pending' => '⏳ Pendiente',
            'created' => '✅ Creada',
            'failed' => '❌ Error',
        ];

        return $badges[$this->status] ?? $this->status;
    }

    public function getFormattedBudgetAttribute(): string
    {
        return '$' . number_format($this->daily_budget, 2);
    }

    public function getFormattedDateRangeAttribute(): string
    {
        $start = $this->start_date->format('d/m/Y');
        $end = $this->end_date ? $this->end_date->format('d/m/Y') : 'Sin fecha fin';
        return "{$start} - {$end}";
    }
}
