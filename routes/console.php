<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Configurar el scheduler para tareas de automatización
Schedule::command('automation:run')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
