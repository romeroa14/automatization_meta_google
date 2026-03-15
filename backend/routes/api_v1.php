<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    LeadController,
    ActiveCampaignController,
    ProfileController,
    FacebookAuthController,
    WhatsAppMessageController,
    FacebookDataController
};

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Rutas versionadas de la API (v1)
| Todas las rutas están bajo el prefijo /api/v1
|
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Login (genera token para mobile o cookie para SPA)
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (!\Illuminate\Support\Facades\Auth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }
        
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Si es petición desde mobile (Flutter), devolver token
        if ($request->expectsJson() && $request->header('X-Client-Type') === 'mobile') {
            $token = $user->createToken('mobile-app')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }
        
        // Si es SPA (Vue), devolver usuario (usa cookies)
        return response()->json(['user' => $user]);
    });
    
    // Logout
    Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    });
    
    // Usuario actual
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return response()->json($request->user());
    });
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/token', [ProfileController::class, 'generateToken']);
    });
    
    // CRM - Leads Management
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index']);
        Route::post('/', [LeadController::class, 'store']);
        Route::get('/{id}', [LeadController::class, 'show']);
        Route::put('/{id}', [LeadController::class, 'update']);
        Route::delete('/{id}', [LeadController::class, 'destroy']);
        Route::get('/{id}/conversations', [LeadController::class, 'conversations']);
    });
    
    // Marketing - Campaigns Management
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [ActiveCampaignController::class, 'index']);
        Route::post('/', [ActiveCampaignController::class, 'store']);
        Route::get('/{id}', [ActiveCampaignController::class, 'show']);
        Route::put('/{id}', [ActiveCampaignController::class, 'update']);
        Route::delete('/{id}', [ActiveCampaignController::class, 'destroy']);
    });
    
    // Facebook Integration
    Route::prefix('facebook')->group(function () {
        Route::get('/login-url', [FacebookAuthController::class, 'getLoginUrl']);
        Route::post('/callback', [FacebookAuthController::class, 'handleCallback']);
        Route::get('/status', [FacebookAuthController::class, 'getConnectionStatus']);
        Route::post('/disconnect', [FacebookAuthController::class, 'disconnect']);
        Route::post('/select-assets', [FacebookDataController::class, 'selectAssets']);
        Route::get('/campaigns', [FacebookDataController::class, 'getCampaigns']);
    });
    
    // WhatsApp Integration
    Route::prefix('whatsapp')->group(function () {
        Route::post('/send', [WhatsAppMessageController::class, 'sendMessage']);
        Route::post('/toggle-bot', [WhatsAppMessageController::class, 'toggleBot']);
    });
    
    // Exchange Rates
    Route::prefix('exchange-rates')->group(function () {
        Route::get('/', function () {
            return \App\Models\ExchangeRate::getAllLatestRates();
        });
        Route::get('/{currency}', function ($currency) {
            return \App\Models\ExchangeRate::getLatestRate($currency);
        });
    });
    
    // Organizations Management
    Route::prefix('organizations')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\OrganizationController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\OrganizationController::class, 'store']);
        Route::get('/{organization}', [\App\Http\Controllers\Api\OrganizationController::class, 'show']);
        Route::put('/{organization}', [\App\Http\Controllers\Api\OrganizationController::class, 'update']);
        Route::delete('/{organization}', [\App\Http\Controllers\Api\OrganizationController::class, 'destroy']);
        
        // Organization Users
        Route::post('/{organization}/users', [\App\Http\Controllers\Api\OrganizationController::class, 'addUser']);
        Route::delete('/{organization}/users/{userId}', [\App\Http\Controllers\Api\OrganizationController::class, 'removeUser']);
        
        // WhatsApp Phone Numbers
        Route::prefix('/{organization}/phone-numbers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'store']);
            Route::get('/{phoneNumber}', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'show']);
            Route::put('/{phoneNumber}', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'update']);
            Route::delete('/{phoneNumber}', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'destroy']);
            Route::post('/{phoneNumber}/verify', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'verify']);
            Route::post('/{phoneNumber}/set-default', [\App\Http\Controllers\Api\WhatsAppPhoneNumberController::class, 'setDefault']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => 'v1',
        'timestamp' => now(),
    ]);
});
