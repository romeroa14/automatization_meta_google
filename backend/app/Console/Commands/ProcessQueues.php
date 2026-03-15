<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queues:process {--timeout=60 : Tiempo máximo de ejecución en segundos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa las colas de trabajo automáticamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        
        $this->info("🔄 Procesando colas de trabajo...");
        $this->info("⏱️  Tiempo máximo: {$timeout} segundos");
        
        try {
            // Ejecutar el worker de colas
            $this->call('queue:work', [
                '--timeout' => $timeout,
                '--tries' => 3,
                '--max-jobs' => 10,
                '--max-time' => $timeout,
                '--stop-when-empty' => true,
            ]);
            
            $this->info("✅ Procesamiento de colas completado");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error procesando colas: " . $e->getMessage());
            return 1;
        }
    }
}
