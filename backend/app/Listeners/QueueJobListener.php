<?php

namespace App\Listeners;

use App\Models\QueueJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;

class QueueJobListener
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        if ($event instanceof JobProcessing) {
            $this->handleJobProcessing($event);
        } elseif ($event instanceof JobProcessed) {
            $this->handleJobProcessed($event);
        } elseif ($event instanceof JobFailed) {
            $this->handleJobFailed($event);
        } elseif ($event instanceof JobExceptionOccurred) {
            $this->handleJobException($event);
        }
    }

    private function handleJobProcessing(JobProcessing $event): void
    {
        try {
            $jobId = $this->extractJobId($event->job);
            
            if ($jobId) {
                $queueJob = QueueJob::where('id', $jobId)->first();
                
                if ($queueJob) {
                    $queueJob->markAsProcessing();
                    
                    Log::info("Job iniciado", [
                        'job_id' => $jobId,
                        'job_name' => $queueJob->job_name,
                        'queue' => $event->job->getQueue()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en handleJobProcessing: " . $e->getMessage());
        }
    }

    private function handleJobProcessed(JobProcessed $event): void
    {
        try {
            $jobId = $this->extractJobId($event->job);
            
            if ($jobId) {
                $queueJob = QueueJob::where('id', $jobId)->first();
                
                if ($queueJob) {
                    $executionTime = $queueJob->started_at ? 
                        $queueJob->started_at->diffInSeconds(now()) : null;
                    
                    $queueJob->markAsCompleted($executionTime);
                    
                    Log::info("Job completado", [
                        'job_id' => $jobId,
                        'job_name' => $queueJob->job_name,
                        'execution_time' => $executionTime
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en handleJobProcessed: " . $e->getMessage());
        }
    }

    private function handleJobFailed(JobFailed $event): void
    {
        try {
            $jobId = $this->extractJobId($event->job);
            
            if ($jobId) {
                $queueJob = QueueJob::where('id', $jobId)->first();
                
                if ($queueJob) {
                    $errorMessage = $event->exception ? $event->exception->getMessage() : 'Error desconocido';
                    $queueJob->markAsFailed($errorMessage);
                    
                    Log::error("Job fallido", [
                        'job_id' => $jobId,
                        'job_name' => $queueJob->job_name,
                        'error' => $errorMessage
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en handleJobFailed: " . $e->getMessage());
        }
    }

    private function handleJobException(JobExceptionOccurred $event): void
    {
        try {
            $jobId = $this->extractJobId($event->job);
            
            if ($jobId) {
                $queueJob = QueueJob::where('id', $jobId)->first();
                
                if ($queueJob) {
                    $errorMessage = $event->exception ? $event->exception->getMessage() : 'Excepción desconocida';
                    
                    Log::error("Excepción en job", [
                        'job_id' => $jobId,
                        'job_name' => $queueJob->job_name,
                        'exception' => $errorMessage
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en handleJobException: " . $e->getMessage());
        }
    }

    private function extractJobId($job): ?int
    {
        try {
            // Intentar extraer el ID del job desde el payload
            $payload = $job->payload();
            
            // Si el payload ya es un array, usarlo directamente
            if (is_array($payload)) {
                return $payload['id'] ?? null;
            }
            
            // Si es string, decodificarlo
            if (is_string($payload)) {
                $data = json_decode($payload, true);
                return $data['id'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error("Error extrayendo Job ID: " . $e->getMessage());
            return null;
        }
    }
}
