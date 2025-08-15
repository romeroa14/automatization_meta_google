<?php

namespace App\Console\Commands;

use App\Models\AutomationTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class MonitorQueues extends Command
{
    protected $signature = 'automation:monitor {--detailed : Mostrar información detallada}';
    protected $description = 'Monitorea las colas y tareas de automatización';

    public function handle()
    {
        $this->info('🔍 MONITOR DE AUTOMATIZACIÓN');
        $this->line('=' . str_repeat('=', 50));
        
        // 1. Estado de las colas
        $this->showQueueStatus();
        
        // 2. Tareas programadas
        $this->showScheduledTasks();
        
        // 3. Próximas ejecuciones
        $this->showUpcomingExecutions();
        
        // 4. Logs recientes
        if ($this->option('detailed')) {
            $this->showRecentLogs();
        }
        
        return 0;
    }
    
    private function showQueueStatus(): void
    {
        $this->info('📊 ESTADO DE LAS COLAS:');
        
        try {
            $queueSize = Queue::size('default');
            $this->line("  • Cola 'default': {$queueSize} jobs pendientes");
            
            if ($queueSize > 0) {
                $this->warn("  ⚠️  Hay jobs pendientes en la cola");
            } else {
                $this->info("  ✅ Cola vacía");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Error al verificar cola: " . $e->getMessage());
        }
        
        $this->line('');
    }
    
    private function showScheduledTasks(): void
    {
        $this->info('📋 TAREAS PROGRAMADAS:');
        
        $tasks = AutomationTask::active()->get();
        
        if ($tasks->isEmpty()) {
            $this->line('  No hay tareas activas');
            return;
        }
        
        foreach ($tasks as $task) {
            $status = $this->getTaskStatus($task);
            $nextRun = $task->next_run ? $task->next_run->format('Y-m-d H:i:s') : 'No configurado';
            $lastRun = $task->last_run ? $task->last_run->format('Y-m-d H:i:s') : 'Nunca';
            
            $this->line("  • {$task->name} (ID: {$task->id})");
            $this->line("    Estado: {$status}");
            $this->line("    Frecuencia: {$task->frequency}");
            $this->line("    Última ejecución: {$lastRun}");
            $this->line("    Próxima ejecución: {$nextRun}");
            $this->line('');
        }
    }
    
    private function showUpcomingExecutions(): void
    {
        $this->info('⏰ PRÓXIMAS EJECUCIONES:');
        
        $now = now();
        $nextHour = $now->copy()->addHour();
        
        $upcomingTasks = AutomationTask::active()
            ->whereNotNull('next_run')
            ->where('next_run', '>', $now)
            ->where('next_run', '<=', $nextHour)
            ->orderBy('next_run')
            ->get();
        
        if ($upcomingTasks->isEmpty()) {
            $this->line('  No hay ejecuciones programadas en la próxima hora');
        } else {
            foreach ($upcomingTasks as $task) {
                $timeUntil = $task->next_run->diffForHumans();
                $this->line("  • {$task->name}: {$task->next_run->format('H:i:s')} ({$timeUntil})");
            }
        }
        
        $this->line('');
    }
    
    private function showRecentLogs(): void
    {
        $this->info('📝 LOGS RECIENTES:');
        
        // Buscar logs recientes en storage/logs/laravel.log
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -20); // Últimas 20 líneas
            
            foreach ($recentLines as $line) {
                if (str_contains($line, 'Scheduler ejecutado') || 
                    str_contains($line, 'Sincronización completada') ||
                    str_contains($line, 'automation:run')) {
                    $this->line("  " . trim($line));
                }
            }
        }
        
        $this->line('');
    }
    
    private function getTaskStatus(AutomationTask $task): string
    {
        if (!$task->is_active) {
            return '❌ Inactiva';
        }
        
        if (!$task->next_run) {
            return '⏳ Sin programar';
        }
        
        if ($task->next_run->isPast()) {
            return '⚠️  Atrasada';
        }
        
        if ($task->next_run->diffInMinutes(now()) <= 5) {
            return '🚀 Próxima ejecución';
        }
        
        return '✅ Programada';
    }
} 