<?php

namespace App\Console\Commands;

use App\Models\AutomationTask;
use App\Jobs\SyncFacebookAdsToGoogleSheets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledTasks extends Command
{
    protected $signature = 'tasks:run-scheduled {--force : Forzar ejecución de todas las tareas}';
    protected $description = 'Ejecuta automáticamente las tareas de automatización programadas';

    public function handle()
    {
        $this->info('🔄 Verificando tareas programadas...');
        
        $force = $this->option('force');
        $executedCount = 0;
        $skippedCount = 0;
        
        try {
            // Obtener tareas activas
            $tasks = AutomationTask::where('is_active', true)->get();
            
            if ($tasks->isEmpty()) {
                $this->warn('⚠️  No hay tareas activas configuradas.');
                return 0;
            }
            
            $this->info("📊 Encontradas {$tasks->count()} tareas activas");
            
            foreach ($tasks as $task) {
                $shouldRun = $this->shouldTaskRun($task, $force);
                
                if ($shouldRun) {
                    $this->info("🚀 Ejecutando tarea: {$task->name}");
                    
                    try {
                        // Despachar job a la cola
                        SyncFacebookAdsToGoogleSheets::dispatch($task);
                        
                        // Actualizar próxima ejecución
                        $task->update([
                            'next_run' => $task->calculateNextRun(),
                        ]);
                        
                        $executedCount++;
                        
                        Log::info("Tarea programada ejecutada: {$task->name}", [
                            'task_id' => $task->id,
                            'frequency' => $task->frequency,
                            'next_run' => $task->next_run
                        ]);
                        
                    } catch (\Exception $e) {
                        $this->error("❌ Error ejecutando tarea {$task->name}: " . $e->getMessage());
                        Log::error("Error ejecutando tarea programada: {$task->name}", [
                            'task_id' => $task->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                } else {
                    $this->line("⏸️  Saltando tarea: {$task->name} (próxima ejecución: {$task->next_run?->format('d/m/Y H:i')})");
                    $skippedCount++;
                }
            }
            
            $this->newLine();
            $this->info("✅ Resumen de ejecución:");
            $this->info("   🚀 Tareas ejecutadas: {$executedCount}");
            $this->info("   ⏸️  Tareas saltadas: {$skippedCount}");
            
            if ($executedCount > 0) {
                $this->info("🔄 Las tareas han sido enviadas a la cola para procesamiento.");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error verificando tareas programadas: ' . $e->getMessage());
            Log::error('Error en RunScheduledTasks: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Determina si una tarea debe ejecutarse
     */
    private function shouldTaskRun(AutomationTask $task, bool $force = false): bool
    {
        if ($force) {
            return true;
        }
        
        // Si no tiene próxima ejecución programada, no ejecutar
        if (!$task->next_run) {
            return false;
        }
        
        // Verificar si es hora de ejecutar
        $now = now();
        
        return $now->gte($task->next_run);
    }
}
