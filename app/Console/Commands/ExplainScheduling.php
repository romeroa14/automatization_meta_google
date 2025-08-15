<?php

namespace App\Console\Commands;

use App\Models\AutomationTask;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ExplainScheduling extends Command
{
    protected $signature = 'automation:explain {--task-id= : ID específico de la tarea}';
    protected $description = 'Explica cómo funciona el sistema de programación';

    public function handle()
    {
        $taskId = $this->option('task-id');
        
        if ($taskId) {
            $task = AutomationTask::find($taskId);
            if (!$task) {
                $this->error("Tarea con ID {$taskId} no encontrada.");
                return 1;
            }
            $this->explainTask($task);
        } else {
            $this->explainSystem();
            $this->explainAllTasks();
        }
        
        return 0;
    }
    
    private function explainSystem(): void
    {
        $this->info('🔍 EXPLICACIÓN DEL SISTEMA DE PROGRAMACIÓN');
        $this->line('=' . str_repeat('=', 60));
        
        $this->line('');
        $this->info('📋 CONCEPTOS CLAVE:');
        $this->line('1. scheduled_time: Hora específica del día (ej: 08:00)');
        $this->line('2. next_run: Fecha y hora exacta de la próxima ejecución');
        $this->line('3. Cron job: Se ejecuta cada minuto para verificar tareas');
        $this->line('');
        
        $this->info('⚙️ CÓMO FUNCIONA:');
        $this->line('• El cron ejecuta "php artisan schedule:run" cada minuto');
        $this->line('• El scheduler busca tareas con next_run <= ahora');
        $this->line('• Si encuentra tareas, las ejecuta y actualiza next_run');
        $this->line('• next_run se calcula basado en frequency + scheduled_time');
        $this->line('');
        
        $this->info('⏰ EJEMPLOS:');
        $this->line('• Frecuencia: daily, Hora: 08:00 → Se ejecuta todos los días a las 8 AM');
        $this->line('• Frecuencia: hourly, Hora: 30 → Se ejecuta cada hora a los 30 minutos');
        $this->line('• Frecuencia: weekly, Hora: 09:00 → Se ejecuta cada lunes a las 9 AM');
        $this->line('');
    }
    
    private function explainAllTasks(): void
    {
        $tasks = AutomationTask::all();
        
        if ($tasks->isEmpty()) {
            $this->warn('No hay tareas configuradas.');
            return;
        }
        
        $this->info('📋 TAREAS CONFIGURADAS:');
        $this->line('');
        
        foreach ($tasks as $task) {
            $this->explainTask($task, false);
            $this->line('');
        }
    }
    
    private function explainTask(AutomationTask $task, bool $showHeader = true): void
    {
        if ($showHeader) {
            $this->info("🔍 ANÁLISIS DE LA TAREA: {$task->name}");
            $this->line('=' . str_repeat('=', 50));
        }
        
        $this->line("📝 Tarea: {$task->name} (ID: {$task->id})");
        $this->line("🔄 Frecuencia: {$task->frequency}");
        $this->line("⏰ Hora programada: " . ($task->scheduled_time ? $task->scheduled_time->format('H:i') : 'No configurada'));
        $this->line("📅 Próxima ejecución: " . ($task->next_run ? $task->next_run->format('Y-m-d H:i:s') : 'No configurada'));
        $this->line("✅ Última ejecución: " . ($task->last_run ? $task->last_run->format('Y-m-d H:i:s') : 'Nunca'));
        $this->line("🔧 Estado: " . ($task->is_active ? 'Activa' : 'Inactiva'));
        
        // Calcular cuándo debería ejecutarse
        $shouldRun = $task->next_run && $task->next_run->isPast();
        $this->line("🚀 ¿Debería ejecutarse ahora? " . ($shouldRun ? 'SÍ' : 'NO'));
        
        if ($task->next_run) {
            $timeUntil = $task->next_run->diffForHumans();
            $this->line("⏳ Tiempo hasta la próxima ejecución: {$timeUntil}");
        }
        
        // Explicar la lógica
        $this->line('');
        $this->info('🧠 LÓGICA DE PROGRAMACIÓN:');
        
        if ($task->scheduled_time) {
            $this->line("• Hora fija: {$task->scheduled_time->format('H:i')}");
            $this->line("• Frecuencia: {$task->frequency}");
            
            $nextCalculated = $task->calculateNextRun();
            if ($nextCalculated) {
                $this->line("• Próxima ejecución calculada: {$nextCalculated->format('Y-m-d H:i:s')}");
            }
        } else {
            $this->line("• No hay hora fija configurada");
            $this->line("• Se ejecutará cada {$task->frequency} desde la última ejecución");
        }
    }
} 