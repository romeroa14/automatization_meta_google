<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'facebook_account_id',
        'advertising_plan_id',
        'meta_campaign_id',
        'meta_campaign_name',
        'meta_adset_id',
        'meta_ad_id',
        'client_name',
        'client_type',
        'daily_budget',
        'duration_days',
        'total_budget',
        'client_price',
        'profit_margin',
        'actual_spend',
        'remaining_budget',
        'status',
        'campaign_start_date',
        'campaign_end_date',
        'meta_data',
        'notes',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'client_price' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'actual_spend' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
        'campaign_start_date' => 'date',
        'campaign_end_date' => 'date',
        'meta_data' => 'array',
    ];

    /**
     * Relación con la cuenta de Facebook
     */
    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class);
    }

    /**
     * Relación con el plan de publicidad
     */
    public function advertisingPlan(): BelongsTo
    {
        return $this->belongsTo(AdvertisingPlan::class);
    }

    /**
     * Relación con transacciones contables
     */
    public function accountingTransactions(): HasMany
    {
        return $this->hasMany(AccountingTransaction::class);
    }

    /**
     * Scope para campañas activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para campañas pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para campañas completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Calcular presupuesto restante
     */
    public function calculateRemainingBudget(): float
    {
        return max(0, $this->total_budget - $this->actual_spend);
    }

    /**
     * Verificar si la campaña está dentro del presupuesto
     */
    public function isWithinBudget(): bool
    {
        return $this->actual_spend <= $this->total_budget;
    }

    /**
     * Obtener porcentaje de presupuesto gastado
     */
    public function getBudgetUsagePercentage(): float
    {
        if ($this->total_budget > 0) {
            return ($this->actual_spend / $this->total_budget) * 100;
        }
        return 0;
    }

    /**
     * Obtener días restantes de la campaña
     */
    public function getRemainingDays(): int
    {
        if ($this->campaign_end_date && $this->campaign_start_date) {
            $endDate = \Carbon\Carbon::parse($this->campaign_end_date);
            $startDate = \Carbon\Carbon::parse($this->campaign_start_date);
            $today = \Carbon\Carbon::today();
            
            if ($today->gt($endDate)) {
                return 0;
            }
            
            return $today->diffInDays($endDate);
        }
        
        return 0;
    }

    /**
     * Actualizar gasto real desde Meta
     */
    public function updateActualSpend(float $newSpend): void
    {
        $this->actual_spend = $newSpend;
        $this->remaining_budget = $this->calculateRemainingBudget();
        
        // Actualizar estado si se agotó el presupuesto
        if ($this->actual_spend >= $this->total_budget) {
            $this->status = 'completed';
        }
        
        $this->save();
    }

    /**
     * Obtener estadísticas de la conciliación
     */
    public function getReconciliationStats(): array
    {
        return [
            'budget_usage_percentage' => $this->getBudgetUsagePercentage(),
            'remaining_budget' => $this->calculateRemainingBudget(),
            'remaining_days' => $this->getRemainingDays(),
            'is_within_budget' => $this->isWithinBudget(),
            'profit_status' => $this->client_price ? 'profitable' : 'pending',
            'daily_average_spend' => $this->duration_days > 0 ? $this->actual_spend / $this->duration_days : 0,
        ];
    }
}
