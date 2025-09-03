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
        'transaction_type',
        'description',
        'amount',
        'currency',
        'status',
        'reference_number',
        'client_name',
        'meta_campaign_id',
        'transaction_date',
        'due_date',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Relación con la conciliación de campaña
     */
    public function campaignReconciliation(): BelongsTo
    {
        return $this->belongsTo(CampaignReconciliation::class);
    }

    /**
     * Relación con el plan de publicidad
     */
    public function advertisingPlan(): BelongsTo
    {
        return $this->belongsTo(AdvertisingPlan::class);
    }

    /**
     * Scope para transacciones de ingresos
     */
    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'income');
    }

    /**
     * Scope para transacciones de gastos
     */
    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }

    /**
     * Scope para transacciones de ganancias
     */
    public function scopeProfit($query)
    {
        return $query->where('transaction_type', 'profit');
    }

    /**
     * Scope para transacciones completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para transacciones pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Verificar si es una transacción de ingreso
     */
    public function isIncome(): bool
    {
        return $this->transaction_type === 'income';
    }

    /**
     * Verificar si es una transacción de gasto
     */
    public function isExpense(): bool
    {
        return $this->transaction_type === 'expense';
    }

    /**
     * Verificar si es una transacción de ganancia
     */
    public function isProfit(): bool
    {
        return $this->transaction_type === 'profit';
    }

    /**
     * Obtener el monto formateado con moneda
     */
    public function getFormattedAmount(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
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

    /**
     * Obtener el tipo de transacción formateado
     */
    public function getTransactionTypeLabel(): string
    {
        return match($this->transaction_type) {
            'income' => 'Ingreso',
            'expense' => 'Gasto',
            'profit' => 'Ganancia',
            'refund' => 'Reembolso',
            default => 'Desconocido'
        };
    }

    /**
     * Calcular días de vencimiento
     */
    public function getDaysUntilDue(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        $dueDate = \Carbon\Carbon::parse($this->due_date);
        $today = \Carbon\Carbon::today();

        if ($today->gt($dueDate)) {
            return -$today->diffInDays($dueDate); // Días vencidos (negativo)
        }

        return $today->diffInDays($dueDate);
    }

    /**
     * Verificar si la transacción está vencida
     */
    public function isOverdue(): bool
    {
        return $this->getDaysUntilDue() < 0;
    }

    /**
     * Obtener estadísticas de la transacción
     */
    public function getTransactionStats(): array
    {
        return [
            'is_income' => $this->isIncome(),
            'is_expense' => $this->isExpense(),
            'is_profit' => $this->isProfit(),
            'is_overdue' => $this->isOverdue(),
            'days_until_due' => $this->getDaysUntilDue(),
            'formatted_amount' => $this->getFormattedAmount(),
            'status_label' => $this->getStatusLabel(),
            'type_label' => $this->getTransactionTypeLabel(),
        ];
    }
}
