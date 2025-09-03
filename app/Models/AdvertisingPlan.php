<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvertisingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_name',
        'description',
        'daily_budget',
        'duration_days',
        'total_budget',
        'client_price',
        'profit_margin',
        'profit_percentage',
        'is_active',
        'features',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'client_price' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    /**
     * Relación con conciliaciones de campañas
     */
    public function campaignReconciliations(): HasMany
    {
        return $this->hasMany(CampaignReconciliation::class);
    }

    /**
     * Relación con transacciones contables
     */
    public function accountingTransactions(): HasMany
    {
        return $this->hasMany(AccountingTransaction::class);
    }

    /**
     * Scope para planes activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calcular automáticamente el presupuesto total
     */
    public function calculateTotalBudget(): float
    {
        return $this->daily_budget * $this->duration_days;
    }

    /**
     * Calcular automáticamente la ganancia
     */
    public function calculateProfitMargin(): float
    {
        return $this->client_price - $this->total_budget;
    }

    /**
     * Calcular automáticamente el porcentaje de ganancia
     */
    public function calculateProfitPercentage(): float
    {
        if ($this->total_budget > 0) {
            return ($this->profit_margin / $this->total_budget) * 100;
        }
        return 0;
    }

    /**
     * Verificar si un presupuesto diario y duración coinciden con este plan
     */
    public function matchesBudgetAndDuration(float $dailyBudget, int $durationDays): bool
    {
        return abs($this->daily_budget - $dailyBudget) < 0.01 && 
               $this->duration_days === $durationDays;
    }

    /**
     * Obtener estadísticas del plan
     */
    public function getStats(): array
    {
        return [
            'total_campaigns' => $this->campaignReconciliations()->count(),
            'active_campaigns' => $this->campaignReconciliations()->where('status', 'active')->count(),
            'total_revenue' => $this->accountingTransactions()
                ->where('transaction_type', 'income')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_expenses' => $this->accountingTransactions()
                ->where('transaction_type', 'expense')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_profit' => $this->accountingTransactions()
                ->where('transaction_type', 'profit')
                ->where('status', 'completed')
                ->sum('amount'),
        ];
    }
}
