<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// Ruta directa para webhook de Telegram (sin middleware CSRF)
Route::post('/telegram-webhook', [TelegramWebhookController::class, 'handle'])->withoutMiddleware(['web']);

// Rutas para reportes
Route::prefix('api/reports')->group(function () {
    Route::post('{report}/generate', [ReportController::class, 'generateReport'])->name('reports.generate');
    Route::get('{report}/status', [ReportController::class, 'getReportStatus'])->name('reports.status');
    Route::get('{report}/stats', [ReportController::class, 'getReportStats'])->name('reports.stats');
});

// Ruta para generar PDF de reportes
Route::get('/reports/{report}/generate-pdf', [App\Http\Controllers\ReportPdfController::class, 'generatePdf'])
    ->name('reports.generate-pdf');

/*
|--------------------------------------------------------------------------
| Telegram Bot Routes
|--------------------------------------------------------------------------
|
| Rutas para el bot de Telegram que permite crear campañas de Meta
|
*/

// Ruta directa para webhook de Telegram (sin middleware CSRF)
Route::post('/api/telegram/webhook', [TelegramWebhookController::class, 'handle']);

Route::prefix('api/telegram')->group(function () {
    // Configurar webhook (para uso administrativo)
    Route::post('/set-webhook', [TelegramWebhookController::class, 'setWebhook']);
    
    // Obtener información del bot
    Route::get('/bot-info', [TelegramWebhookController::class, 'getBotInfo']);
});

/*
|--------------------------------------------------------------------------
| Exchange Rates Routes
|--------------------------------------------------------------------------
|
| Rutas para el sistema de tasas de cambio
|
*/

Route::prefix('api/exchange-rates')->group(function () {
    Route::get('/', function () {
        return \App\Models\ExchangeRate::getAllLatestRates();
    });
    
    Route::get('/{currency}', function ($currency) {
        return \App\Models\ExchangeRate::getLatestRate($currency);
    });
    
    Route::post('/update', function () {
        Artisan::call('exchange:update-all');
        return response()->json(['status' => 'success', 'message' => 'Tasas actualizadas']);
    });
});

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Rutas para webhooks externos
|
*/

Route::prefix('api/webhook')->group(function () {
    Route::post('/bcv/update-rates', function () {
        Artisan::call('exchange:update-all', ['--source' => 'BCV']);
        return response()->json(['status' => 'success', 'message' => 'Tasas BCV actualizadas']);
    });
    
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    });
    
    Route::post('/bcv/cleanup', function () {
        \App\Models\ExchangeRate::cleanOldRates();
        return response()->json(['status' => 'success', 'message' => 'Limpieza completada']);
    });
});
