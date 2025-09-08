<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignPlanReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'active_campaign_id',
        'advertising_plan_id',
        'reconciliation_status',
        'reconciliation_date',
        'notes',
        'planned_budget',
        'actual_spent',
        'variance',
        'variance_percentage',
        'reconciliation_data',
        'last_updated_at',
    ];

    protected $casts = [
        'reconciliation_date' => 'datetime',
        'last_updated_at' => 'datetime',
        'planned_budget' => 'decimal:2',
        'actual_spent' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'reconciliation_data' => 'array',
    ];

    /**
     * Relación con ActiveCampaign
     */
    public function activeCampaign(): BelongsTo
    {
        return $this->belongsTo(ActiveCampaign::class);
    }

    /**
     * Relación con AdvertisingPlan
     */
    public function advertisingPlan(): BelongsTo
    {
        return $this->belongsTo(AdvertisingPlan::class);
    }

    /**
     * Calcular la variación automáticamente
     */
    public function calculateVariance(): void
    {
        if ($this->planned_budget && $this->actual_spent) {
            $this->variance = $this->planned_budget - $this->actual_spent;
            $this->variance_percentage = $this->planned_budget > 0 
                ? ($this->variance / $this->planned_budget) * 100 
                : 0;
        }
    }

    /**
     * Obtener el estado de la conciliación con colores
     */
    public function getStatusColor(): string
    {
        return match($this->reconciliation_status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'completed' => 'info',
            'paused' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Obtener el estado de la conciliación en español
     */
    public function getStatusLabel(): string
    {
        return match($this->reconciliation_status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'completed' => 'Completada',
            'paused' => 'Pausada',
            default => 'Desconocido',
        };
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('reconciliation_status', $status);
    }

    /**
     * Scope para conciliaciones pendientes
     */
    public function scopePending($query)
    {
        return $query->where('reconciliation_status', 'pending');
    }

    /**
     * Scope para conciliaciones completadas
     */
    public function scopeCompleted($query)
    {
        return $query->where('reconciliation_status', 'completed');
    }

    /**
     * Scope para conciliaciones pausadas
     */
    public function scopePaused($query)
    {
        return $query->where('reconciliation_status', 'paused');
    }
}