<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QueueJob extends Model
{
    use HasFactory;

    protected $table = 'queue_jobs';

    protected $fillable = [
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
        'created_at',
        'job_type',
        'job_data',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'execution_time'
    ];

    protected $casts = [
        'job_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // Scopes para filtrar por estado
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Accessors
    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => '⏳ Pendiente',
            'processing' => '🔄 Procesando',
            'completed' => '✅ Completado',
            'failed' => '❌ Fallido',
            default => $this->status
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    // Métodos para actualizar estado
    public function markAsProcessing()
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
            'reserved_at' => now()->timestamp
        ]);
    }

    public function markAsCompleted($executionTime = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'execution_time' => $executionTime
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    // Método para obtener información del job desde el payload
    public function getJobInfoAttribute()
    {
        try {
            $payload = json_decode($this->payload, true);
            
            if (isset($payload['displayName'])) {
                return [
                    'name' => $payload['displayName'],
                    'data' => $payload['data'] ?? [],
                    'maxTries' => $payload['maxTries'] ?? null,
                    'timeout' => $payload['timeout'] ?? null,
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Método para obtener el nombre del job
    public function getJobNameAttribute()
    {
        $jobInfo = $this->job_info;
        return $jobInfo['name'] ?? 'Job Desconocido';
    }

    // Método para verificar si el job está atrasado
    public function getIsDelayedAttribute()
    {
        return $this->available_at && $this->available_at->isPast() && $this->status === 'pending';
    }

    // Método para obtener el tiempo de espera
    public function getWaitTimeAttribute()
    {
        if ($this->status === 'pending' && $this->available_at) {
            return $this->available_at->diffForHumans();
        }
        return null;
    }
}
