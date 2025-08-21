<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas para reportes
Route::prefix('api/reports')->group(function () {
    Route::post('{report}/generate', [ReportController::class, 'generateReport'])->name('reports.generate');
    Route::get('{report}/status', [ReportController::class, 'getReportStatus'])->name('reports.status');
    Route::get('{report}/stats', [ReportController::class, 'getReportStats'])->name('reports.stats');
});
