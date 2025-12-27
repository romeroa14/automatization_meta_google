<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Api\FacebookAuthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    try {
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('mobile-app')->plainTextToken;
            return response()->json(['token' => $token, 'user' => $user]);
        }
        
        return response()->json(['message' => 'Unauthorized'], 401);
    } catch (\Exception $e) {
        \Log::error('Login error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'email' => $request->input('email'),
        ]);
        
        return response()->json([
            'message' => 'Server Error',
            'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| Facebook OAuth Routes
|--------------------------------------------------------------------------
|
| Rutas para autenticación OAuth de Facebook para clientes
|
*/

Route::prefix('auth/facebook')->group(function () {
    // Obtener URL de login (público, para iniciar el flujo)
    Route::get('/login-url', [FacebookAuthController::class, 'getLoginUrl']);
    
    // Callback de Facebook (ahora protegido para tener acceso al usuario)
    // Route::post('/callback', [FacebookAuthController::class, 'handleCallback']);
    
    // Rutas protegidas (requieren autenticación)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/callback', [FacebookAuthController::class, 'handleCallback']);
        Route::get('/status', [FacebookAuthController::class, 'getConnectionStatus']);
        Route::post('/disconnect', [FacebookAuthController::class, 'disconnect']);
        
        // Perfil de Usuario
        Route::get('/profile', [App\Http\Controllers\Api\ProfileController::class, 'show']);
        Route::post('/profile', [App\Http\Controllers\Api\ProfileController::class, 'update']);
        Route::post('/profile/token', [App\Http\Controllers\Api\ProfileController::class, 'generateToken']);

        // Webhook para n8n (Recibir Leads)
        Route::post('/leads/webhook', [App\Http\Controllers\Api\LeadWebhookController::class, 'handle']);
        
        // Rutas de Datos de Facebookute::post('/select-assets', [\App\Http\Controllers\Api\FacebookDataController::class, 'selectAssets']);
        Route::get('/campaigns', [\App\Http\Controllers\Api\FacebookDataController::class, 'getCampaigns']);
    });
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
