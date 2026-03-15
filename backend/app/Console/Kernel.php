<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecutar tareas de automatización cada minuto
        $schedule->command('tasks:run-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
            
        // Limpiar logs antiguos cada día
        $schedule->command('queue:prune-failed')
            ->daily()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
            
        // Limpiar jobs antiguos cada semana
        $schedule->command('queue:prune-batches')
            ->weekly()
            ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
