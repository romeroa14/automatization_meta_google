<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_task_id',
        'started_at',
        'completed_at',
        'status',
        'message',
        'error_message',
        'records_processed',
        'execution_time',
        'data_synced'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'data_synced' => 'array',
    ];

    public function automationTask(): BelongsTo
    {
        return $this->belongsTo(AutomationTask::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeError($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'success' => 'Exitoso',
            'failed' => 'Fallido',
            'running' => 'Ejecutando',
            default => $this->status
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'running' => 'warning',
            default => 'secondary'
        };
    }

    public function getFormattedExecutionTimeAttribute(): string
    {
        return number_format($this->execution_time, 2) . 's';
    }

    public function getFormattedStartedAtAttribute(): string
    {
        return $this->started_at->format('d/m/Y H:i:s');
    }
}
