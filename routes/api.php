<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // CRM Routes
    Route::apiResource('leads', \App\Http\Controllers\Api\LeadController::class);
    Route::get('leads/{id}/conversations', [\App\Http\Controllers\Api\LeadController::class, 'conversations']);
    
    // Marketing Routes
    Route::apiResource('campaigns', \App\Http\Controllers\Api\ActiveCampaignController::class);
});

// Auth Routes (Temporary for dev using Sanctum SPA or Token)
Route::post('/login', function (Request $request) {
    $credentials = $request->only('email', 'password');
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }
    return response()->json(['message' => 'Unauthorized'], 401);
});

/*
|--------------------------------------------------------------------------
| Telegram Bot Routes
|--------------------------------------------------------------------------
|
| Rutas para el bot de Telegram que permite crear campañas de Meta
|
*/

Route::prefix('telegram')->group(function () {
    // Webhook para recibir mensajes de Telegram
    Route::post('/webhook', [TelegramWebhookController::class, 'handle']);
    
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

Route::prefix('exchange-rates')->group(function () {
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

Route::prefix('webhook')->group(function () {
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
