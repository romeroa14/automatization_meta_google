<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Configurar el scheduler para tareas de automatización
// Solo ejecutar tareas que realmente están programadas para ejecutarse
Schedule::command('automation:run')
    ->everyMinute() // Verificar cada minuto, pero el comando decide qué ejecutar
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Verificar y ejecutar tareas de automatización programadas');
