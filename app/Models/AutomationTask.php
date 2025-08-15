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
                    ->where(function ($q) {
                        $q->whereNull('next_run')
                          ->orWhere('next_run', '<=', now());
                    });
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
        $baseTime = $this->scheduled_time ? $now->copy()->setTimeFrom($this->scheduled_time) : $now;

        return match($this->frequency) {
            'hourly' => $baseTime->addHour(),
            'daily' => $baseTime->addDay(),
            'weekly' => $baseTime->addWeek(),
            'monthly' => $baseTime->addMonth(),
            'custom' => null, // Se maneja manualmente
            default => $baseTime->addDay(),
        };
    }

    public function getLastLogAttribute()
    {
        return $this->taskLogs()->latest()->first();
    }
}
