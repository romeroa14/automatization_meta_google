<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AutomationTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'facebook_account_id',
        'google_sheet_id',
        'frequency',
        'scheduled_time',
        'is_active',
        'last_run',
        'next_run',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
        'scheduled_time' => 'datetime',
        'settings' => 'array',
    ];

    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class);
    }

    public function googleSheet(): BelongsTo
    {
        return $this->belongsTo(GoogleSheet::class);
    }

    public function taskLogs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForExecution($query)
    {
        return $query->where('is_active', true)
                    ->whereNotNull('next_run') // Solo tareas que tienen next_run configurado
                    ->where('next_run', '<=', now());
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'hourly' => 'Cada hora',
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'monthly' => 'Mensual',
            'custom' => 'Personalizado',
            default => $this->frequency
        };
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if (!$this->last_run) {
            return 'pending';
        }
        
        if ($this->next_run && $this->next_run->isPast()) {
            return 'overdue';
        }
        
        return 'active';
    }

    public function calculateNextRun(): ?Carbon
    {
        if (!$this->is_active) {
            return null;
        }

        $now = now();
        
        // Si hay scheduled_time configurado, usar esa hora específica
        if ($this->scheduled_time) {
            $scheduledHour = $this->scheduled_time->hour;
            $scheduledMinute = $this->scheduled_time->minute;
            
            // Calcular la próxima ejecución basada en la frecuencia y hora programada
            $nextRun = match($this->frequency) {
                'hourly' => $now->copy()->addHour()->setTime($scheduledHour, $scheduledMinute),
                'daily' => $now->copy()->addDay()->setTime($scheduledHour, $scheduledMinute),
                'weekly' => $now->copy()->addWeek()->setTime($scheduledHour, $scheduledMinute),
                'monthly' => $now->copy()->addMonth()->setTime($scheduledHour, $scheduledMinute),
                'custom' => null, // Se maneja manualmente
                default => $now->copy()->addDay()->setTime($scheduledHour, $scheduledMinute),
            };
            
            // Si la próxima ejecución ya pasó, calcular la siguiente
            if ($nextRun && $nextRun->isPast()) {
                $nextRun = match($this->frequency) {
                    'hourly' => $nextRun->addHour(),
                    'daily' => $nextRun->addDay(),
                    'weekly' => $nextRun->addWeek(),
                    'monthly' => $nextRun->addMonth(),
                    default => $nextRun->addDay(),
                };
            }
            
            return $nextRun;
        }
        
        // Si no hay scheduled_time, usar la hora actual + frecuencia
        return match($this->frequency) {
            'hourly' => $now->copy()->addHour(),
            'daily' => $now->copy()->addDay(),
            'weekly' => $now->copy()->addWeek(),
            'monthly' => $now->copy()->addMonth(),
            'custom' => null, // Se maneja manualmente
            default => $now->copy()->addDay(),
        };
    }

    public function getLastLogAttribute()
    {
        return $this->taskLogs()->latest()->first();
    }
}
