<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use App\Models\AutomationTask;
use App\Models\TaskLog;
use App\Models\FacebookAccount;
use App\Models\GoogleSheet;

class CheckSystemStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica el estado general del sistema de automatización';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando estado del sistema de automatización...');
        $this->newLine();
        
        // 1. Verificar base de datos
        $this->checkDatabase();
        
        // 2. Verificar colas
        $this->checkQueues();
        
        // 3. Verificar configuración
        $this->checkConfiguration();
        
        // 4. Verificar tareas
        $this->checkTasks();
        
        // 5. Verificar logs recientes
        $this->checkRecentLogs();
        
        $this->newLine();
        $this->info('✅ Verificación completada');
    }
    
    private function checkDatabase(): void
    {
        $this->info('1️⃣ Verificando base de datos...');
        
        try {
            DB::connection()->getPdo();
            $this->info('   ✅ Conexión a base de datos: OK');
            
            // Verificar tablas importantes
            $tables = ['facebook_accounts', 'google_sheets', 'automation_tasks', 'task_logs', 'jobs'];
            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $count = DB::table($table)->count();
                    $this->info("   📊 Tabla {$table}: {$count} registros");
                } else {
                    $this->error("   ❌ Tabla {$table}: NO EXISTE");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error de base de datos: ' . $e->getMessage());
        }
    }
    
    private function checkQueues(): void
    {
        $this->info('2️⃣ Verificando colas de trabajo...');
        
        try {
            // Verificar jobs pendientes
            $pendingJobs = DB::table('jobs')->count();
            $this->info("   📋 Jobs pendientes: {$pendingJobs}");
            
            // Verificar jobs fallidos
            $failedJobs = DB::table('failed_jobs')->count();
            $this->info("   ❌ Jobs fallidos: {$failedJobs}");
            
            // Verificar si hay queue workers ejecutándose
            $workers = shell_exec("ps aux | grep 'queue:work' | grep -v grep | wc -l");
            $workers = trim($workers);
            
            if ($workers > 0) {
                $this->info("   ✅ Queue workers ejecutándose: {$workers}");
            } else {
                $this->warn("   ⚠️  No hay queue workers ejecutándose");
                $this->info("   💡 Para iniciar: php artisan queue:work --daemon");
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error verificando colas: ' . $e->getMessage());
        }
    }
    
    private function checkConfiguration(): void
    {
        $this->info('3️⃣ Verificando configuración...');
        
        // Verificar variables de entorno importantes
        $envVars = [
            'DB_CONNECTION' => 'Conexión BD',
            'GOOGLE_WEBAPP_URL' => 'Google WebApp URL',
            'APP_KEY' => 'App Key',
        ];
        
        foreach ($envVars as $var => $description) {
            $value = env($var);
            if (!empty($value)) {
                $this->info("   ✅ {$description}: Configurado");
            } else {
                $this->error("   ❌ {$description}: NO CONFIGURADO");
            }
        }
        
        // Verificar configuración de colas
        $queueDriver = config('queue.default');
        $this->info("   📋 Driver de colas: {$queueDriver}");
    }
    
    private function checkTasks(): void
    {
        $this->info('4️⃣ Verificando tareas de automatización...');
        
        try {
            $totalTasks = AutomationTask::count();
            $activeTasks = AutomationTask::where('is_active', true)->count();
            $this->info("   📋 Total de tareas: {$totalTasks}");
            $this->info("   ✅ Tareas activas: {$activeTasks}");
            
            // Verificar cuentas de Facebook
            $fbAccounts = FacebookAccount::where('is_active', true)->count();
            $this->info("   📱 Cuentas Facebook activas: {$fbAccounts}");
            
            // Verificar Google Sheets
            $googleSheets = GoogleSheet::where('is_active', true)->count();
            $this->info("   📊 Google Sheets activos: {$googleSheets}");
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error verificando tareas: ' . $e->getMessage());
        }
    }
    
    private function checkRecentLogs(): void
    {
        $this->info('5️⃣ Verificando logs recientes...');
        
        try {
            $recentLogs = TaskLog::where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            if ($recentLogs->count() > 0) {
                $this->info("   📝 Últimos 5 logs (24h):");
                foreach ($recentLogs as $log) {
                    $status = $log->status === 'success' ? '✅' : ($log->status === 'failed' ? '❌' : '⏳');
                    $this->info("      {$status} {$log->created_at->format('H:i:s')} - {$log->status}");
                }
            } else {
                $this->warn("   ⚠️  No hay logs en las últimas 24 horas");
            }
            
        } catch (\Exception $e) {
            $this->error('   ❌ Error verificando logs: ' . $e->getMessage());
        }
    }
}
