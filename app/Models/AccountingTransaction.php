<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_reconciliation_id',
        'advertising_plan_id',
        'description',
        'income',
        'expense',
        'profit',
        'currency',
        'status',
        'reference_number',
        'client_name',
        'meta_campaign_id',
        'campaign_start_date',
        'campaign_end_date',
        'transaction_date',
        'due_date',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'income' => 'decimal:2',
        'expense' => 'decimal:2',
        'profit' => 'decimal:2',
        'campaign_start_date' => 'date',
        'campaign_end_date' => 'date',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Relación con la conciliación de campaña
     */
    public function campaignReconciliation(): BelongsTo
    {
        return $this->belongsTo(CampaignPlanReconciliation::class);
    }

    /**
     * Relación con el plan de publicidad
     */
    public function advertisingPlan(): BelongsTo
    {
        return $this->belongsTo(AdvertisingPlan::class);
    }

    /**
     * Obtener estadísticas de la transacción
     */
    public function getTransactionStats(): array
    {
        return [
            'income' => $this->income,
            'expense' => $this->expense,
            'profit' => $this->profit,
            'formatted_income' => $this->currency . ' ' . number_format($this->income, 2),
            'formatted_expense' => $this->currency . ' ' . number_format($this->expense, 2),
            'formatted_profit' => $this->currency . ' ' . number_format($this->profit, 2),
            'status_label' => $this->getStatusLabel(),
        ];
    }

    /**
     * Obtener el estado formateado
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
            default => 'Desconocido'
        };
    }
}
