<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobExceptionOccurred;
use App\Listeners\QueueJobListener;

class QueueEventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar eventos de cola para monitoreo
        Event::listen([
            JobProcessing::class,
            JobProcessed::class,
            JobFailed::class,
            JobExceptionOccurred::class,
        ], QueueJobListener::class);
    }
}
