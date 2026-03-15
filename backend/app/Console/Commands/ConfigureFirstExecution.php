<?php

namespace App\Console\Commands;

use App\Models\AutomationTask;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ConfigureFirstExecution extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:configure-first-execution 
                            {--task-id= : ID específico de la tarea}
                            {--all : Configurar todas las tareas}
                            {--time= : Hora específica para la primera ejecución (HH:MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura la primera ejecución de tareas de automatización';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 Configurando primera ejecución de tareas de automatización...');
        $this->newLine();

        // Obtener tareas a configurar
        $tasks = $this->getTasksToConfigure();
        
        if ($tasks->isEmpty()) {
            $this->warn('⚠️  No se encontraron tareas para configurar.');
            return 1;
        }

        $this->info("📋 Se encontraron {$tasks->count()} tarea(s) para configurar:");
        $this->newLine();

        // Mostrar tareas
        foreach ($tasks as $task) {
            $this->line("  • {$task->name} (ID: {$task->id})");
            $this->line("    Frecuencia: {$task->frequency}");
            $this->line("    Hora programada: " . ($task->scheduled_time ? $task->scheduled_time->format('H:i') : 'No configurada'));
            $this->line("    Estado: " . ($task->is_active ? 'Activa' : 'Inactiva'));
            $this->line("    Última ejecución: " . ($task->last_run ? $task->last_run->format('Y-m-d H:i:s') : 'Nunca'));
            $this->line("    Próxima ejecución: " . ($task->next_run ? $task->next_run->format('Y-m-d H:i:s') : 'No configurada'));
            $this->newLine();
        }

        // Confirmar configuración
        if (!$this->confirm('¿Deseas configurar la primera ejecución de estas tareas?')) {
            $this->info('❌ Configuración cancelada.');
            return 0;
        }

        // Obtener hora para la primera ejecución
        $firstExecutionTime = $this->getFirstExecutionTime();

        $this->info("⏰ Configurando primera ejecución para: {$firstExecutionTime->format('Y-m-d H:i:s')}");
        $this->newLine();

        $bar = $this->output->createProgressBar($tasks->count());
        $bar->start();

        $configuredCount = 0;
        $errors = [];

        foreach ($tasks as $task) {
            try {
                $this->configureTaskFirstExecution($task, $firstExecutionTime);
                $configuredCount++;
            } catch (\Exception $e) {
                $errors[] = "Error en tarea {$task->name}: " . $e->getMessage();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resultados
        if ($configuredCount > 0) {
            $this->info("✅ Se configuraron {$configuredCount} tarea(s) exitosamente.");
        }

        if (!empty($errors)) {
            $this->error("❌ Errores encontrados:");
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
        }

        $this->newLine();
        $this->info('🎯 Configuración completada. Las tareas se ejecutarán en el horario programado.');
        
        return 0;
    }

    /**
     * Obtiene las tareas a configurar
     */
    private function getTasksToConfigure()
    {
        $query = AutomationTask::query();

        if ($taskId = $this->option('task-id')) {
            $query->where('id', $taskId);
        } elseif ($this->option('all')) {
            // Todas las tareas activas
            $query->where('is_active', true);
        } else {
            // Solo tareas que no tienen next_run configurado
            $query->where('is_active', true)
                  ->whereNull('next_run');
        }

        return $query->get();
    }

    /**
     * Obtiene la hora para la primera ejecución
     */
    private function getFirstExecutionTime(): Carbon
    {
        $time = $this->option('time');
        
        if ($time) {
            // Validar formato de hora
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                throw new \InvalidArgumentException('Formato de hora inválido. Use HH:MM (ej: 09:00)');
            }
            
            $now = now();
            $firstExecution = $now->copy()->setTimeFrom($time);
            
            // Si la hora ya pasó hoy, programar para mañana
            if ($firstExecution->isPast()) {
                $firstExecution->addDay();
            }
            
            return $firstExecution;
        }

        // Si no se especifica hora, preguntar al usuario
        $this->info('⏰ Configuración de primera ejecución:');
        
        $useCurrentTime = $this->confirm('¿Usar la hora actual para la primera ejecución?', false);
        
        if ($useCurrentTime) {
            return now()->addMinutes(5); // 5 minutos después
        }

        $time = $this->ask('Ingresa la hora para la primera ejecución (HH:MM)', '09:00');
        
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $this->error('Formato de hora inválido. Usando 09:00 por defecto.');
            $time = '09:00';
        }

        $now = now();
        $firstExecution = $now->copy()->setTimeFrom($time);
        
        // Si la hora ya pasó hoy, programar para mañana
        if ($firstExecution->isPast()) {
            $firstExecution->addDay();
        }

        return $firstExecution;
    }

    /**
     * Configura la primera ejecución de una tarea
     */
    private function configureTaskFirstExecution(AutomationTask $task, Carbon $firstExecutionTime): void
    {
        // Calcular la próxima ejecución basada en la frecuencia
        $nextRun = $this->calculateNextRun($task, $firstExecutionTime);
        
        // Actualizar la tarea
        $task->update([
            'next_run' => $nextRun,
            'scheduled_time' => $firstExecutionTime->copy()->setTime($firstExecutionTime->hour, $firstExecutionTime->minute)
        ]);

        // Crear log de configuración
        $task->taskLogs()->create([
            'started_at' => now(),
            'completed_at' => now(),
            'status' => 'configured',
            'message' => "Primera ejecución configurada para: {$firstExecutionTime->format('Y-m-d H:i:s')}",
            'execution_time' => 0,
            'records_processed' => 0,
        ]);
    }

    /**
     * Calcula la próxima ejecución basada en la frecuencia
     */
    private function calculateNextRun(AutomationTask $task, Carbon $baseTime): Carbon
    {
        return match($task->frequency) {
            'hourly' => $baseTime->copy()->addHour(),
            'daily' => $baseTime->copy()->addDay(),
            'weekly' => $baseTime->copy()->addWeek(),
            'monthly' => $baseTime->copy()->addMonth(),
            default => $baseTime->copy()->addDay(),
        };
    }
}
