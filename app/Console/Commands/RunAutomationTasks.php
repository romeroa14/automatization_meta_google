<?php

namespace App\Console\Commands;

use App\Jobs\SyncFacebookAdsToGoogleSheets;
use App\Models\AutomationTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunAutomationTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:run {--task-id= : ID específico de la tarea}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta tareas de automatización programadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $taskId = $this->option('task-id');
        
        if ($taskId) {
            // Ejecutar tarea específica
            $task = AutomationTask::find($taskId);
            
            if (!$task) {
                $this->error("Tarea con ID {$taskId} no encontrada.");
                return 1;
            }
            
            if (!$task->is_active) {
                $this->warn("Tarea '{$task->name}' está inactiva.");
                return 1;
            }
            
            $this->info("Ejecutando tarea específica: {$task->name}");
            $this->runTask($task);
            
        } else {
            // Ejecutar todas las tareas programadas
            $tasks = AutomationTask::active()
                ->where('next_run', '<=', now())
                ->get();
            
            if ($tasks->isEmpty()) {
                $this->info('No hay tareas programadas para ejecutar.');
                return 0;
            }
            
            $this->info("Encontradas {$tasks->count()} tarea(s) para ejecutar:");
            
            foreach ($tasks as $task) {
                $this->line("- {$task->name} (ID: {$task->id})");
            }
            
            if ($this->confirm('¿Deseas ejecutar estas tareas?')) {
                foreach ($tasks as $task) {
                    $this->runTask($task);
                }
            }
        }
        
        return 0;
    }
    
    private function runTask(AutomationTask $task): void
    {
        try {
            $this->info("Despachando job para tarea: {$task->name}");
            
            // Despachar el job
            SyncFacebookAdsToGoogleSheets::dispatch($task);
            
            $this->info("✓ Job despachado exitosamente para: {$task->name}");
            
            Log::info("Tarea de automatización despachada", [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'user_id' => $task->user_id,
            ]);
            
        } catch (\Exception $e) {
            $this->error("Error al despachar job para tarea {$task->name}: {$e->getMessage()}");
            
            Log::error("Error al despachar tarea de automatización", [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
