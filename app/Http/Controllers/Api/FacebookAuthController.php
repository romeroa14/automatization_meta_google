<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FacebookAccount;
use App\Models\UserFacebookConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookAuthController extends Controller
{
    protected string $graphApiVersion = 'v18.0';
    protected string $graphApiUrl = 'https://graph.facebook.com';
    
    /**
     * Permisos requeridos para la app
     */
    protected array $requiredScopes = [
        'email',
        'public_profile',
        'ads_read',
        'ads_management',
        'pages_read_engagement',
        'pages_manage_ads',
        'business_management',
        'whatsapp_business_management',
        'whatsapp_business_messaging',
    ];

    /**
     * Generar URL de login de Facebook
     */
    public function getLoginUrl(Request $request)
    {
        // Obtener credenciales desde FacebookAccount (configurado en Filament)
        $oauthAccount = FacebookAccount::getOAuthAccount();
        
        if (!$oauthAccount) {
            return response()->json([
                'success' => false,
                'error' => 'No hay cuenta de Facebook configurada en el sistema. Contacte al administrador.',
            ], 500);
        }
        
        $state = Str::random(40);
        
        // Guardar state en session para validar después
        session(['facebook_oauth_state' => $state]);
        
        // Obtener redirect_uri desde config (esto sí debe estar en .env)
        $redirectUri = config('services.facebook.redirect_uri', 'http://localhost:9000/auth/facebook/callback');
        
        $params = http_build_query([
            'client_id' => $oauthAccount->app_id,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $this->requiredScopes),
            'response_type' => 'code',
            'state' => $state,
        ]);
        
        $loginUrl = "https://www.facebook.com/{$this->graphApiVersion}/dialog/oauth?{$params}";
        
        return response()->json([
            'login_url' => $loginUrl,
            'state' => $state,
        ]);
    }

    /**
     * Manejar callback de Facebook OAuth
     */
    public function handleCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'nullable|string',
        ]);
        
        $code = $request->input('code');
        
        try {
            // 1. Intercambiar code por access_token
            $tokenResponse = $this->exchangeCodeForToken($code);
            
            if (!$tokenResponse['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $tokenResponse['error'],
                ], 400);
            }
            
            $shortLivedToken = $tokenResponse['access_token'];
            
            // 2. Intercambiar por token de larga duración (60 días)
            $longLivedResponse = $this->exchangeForLongLivedToken($shortLivedToken);
            
            if (!$longLivedResponse['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error obteniendo token de larga duración',
                ], 400);
            }
            
            $accessToken = $longLivedResponse['access_token'];
            $expiresIn = $longLivedResponse['expires_in'] ?? 5184000; // 60 días default
            
            // 3. Obtener información del usuario de Facebook
            $userInfo = $this->getFacebookUserInfo($accessToken);
            
            if (!$userInfo['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error obteniendo información del usuario',
                ], 400);
            }
            
            // 4. Obtener Ad Accounts y Pages del usuario
            $adAccounts = $this->getAdAccounts($accessToken);
            $pages = $this->getPages($accessToken);
            
            // 5. Crear o actualizar la conexión en BD
            $connection = $this->createOrUpdateConnection(
                $request->user(), // Usuario autenticado de Laravel
                $userInfo['data'],
                $accessToken,
                $expiresIn,
                $adAccounts,
                $pages
            );
            
            Log::info('✅ Facebook OAuth completado', [
                'user_id' => $request->user()?->id,
                'facebook_user_id' => $userInfo['data']['id'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cuenta de Facebook conectada exitosamente',
                'connection' => [
                    'id' => $connection->id,
                    'facebook_name' => $connection->facebook_name,
                    'facebook_email' => $connection->facebook_email,
                    'ad_accounts' => $connection->ad_accounts,
                    'pages' => $connection->pages,
                    'token_expires_at' => $connection->token_expires_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en Facebook OAuth', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error procesando autenticación: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desconectar cuenta de Facebook
     */
    public function disconnect(Request $request)
    {
        $user = $request->user();
        
        $connection = UserFacebookConnection::where('user_id', $user->id)->first();
        
        if (!$connection) {
            return response()->json([
                'success' => false,
                'error' => 'No hay cuenta de Facebook conectada',
            ], 404);
        }
        
        // Revocar permisos en Facebook (opcional pero recomendado)
        try {
            Http::delete("{$this->graphApiUrl}/{$this->graphApiVersion}/me/permissions", [
                'access_token' => $connection->access_token,
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo revocar permisos en Facebook', [
                'error' => $e->getMessage(),
            ]);
        }
        
        $connection->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cuenta de Facebook desconectada',
        ]);
    }

    /**
     * Obtener estado de conexión actual
     */
    public function getConnectionStatus(Request $request)
    {
        $user = $request->user();
        
        $connection = UserFacebookConnection::where('user_id', $user->id)
            ->active()
            ->first();
        
        if (!$connection) {
            return response()->json([
                'connected' => false,
            ]);
        }
        
        return response()->json([
            'connected' => true,
            'facebook_name' => $connection->facebook_name,
            'token_expires_at' => $connection->token_expires_at,
            'needs_renewal' => $connection->needsRenewal(),
            'ad_accounts' => $connection->ad_accounts,
            'pages' => $connection->pages,
            'selected_ad_account_id' => $connection->selected_ad_account_id,
            'selected_page_id' => $connection->selected_page_id,
        ]);
    }

    /**
     * Intercambiar code por access_token
     */
    protected function exchangeCodeForToken(string $code): array
    {
        $oauthAccount = FacebookAccount::getOAuthAccount();
        
        if (!$oauthAccount) {
            return [
                'success' => false,
                'error' => 'No hay cuenta de Facebook configurada',
            ];
        }
        
        $redirectUri = config('services.facebook.redirect_uri', 'http://localhost:9000/auth/facebook/callback');
        
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/oauth/access_token", [
            'client_id' => $oauthAccount->app_id,
            'client_secret' => $oauthAccount->app_secret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
        
        if ($response->successful() && isset($response->json()['access_token'])) {
            return [
                'success' => true,
                'access_token' => $response->json()['access_token'],
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Error desconocido',
        ];
    }

    /**
     * Intercambiar token corto por token de larga duración
     */
    protected function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $oauthAccount = FacebookAccount::getOAuthAccount();
        
        if (!$oauthAccount) {
            return [
                'success' => false,
                'error' => 'No hay cuenta de Facebook configurada',
            ];
        }
        
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $oauthAccount->app_id,
            'client_secret' => $oauthAccount->app_secret,
            'fb_exchange_token' => $shortLivedToken,
        ]);
        
        if ($response->successful() && isset($response->json()['access_token'])) {
            return [
                'success' => true,
                'access_token' => $response->json()['access_token'],
                'expires_in' => $response->json()['expires_in'] ?? 5184000,
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Error desconocido',
        ];
    }

    /**
     * Obtener información del usuario de Facebook
     */
    protected function getFacebookUserInfo(string $accessToken): array
    {
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/me", [
            'access_token' => $accessToken,
            'fields' => 'id,name,email',
        ]);
        
        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
            ];
        }
        
        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Error desconocido',
        ];
    }

    /**
     * Obtener Ad Accounts del usuario
     */
    protected function getAdAccounts(string $accessToken): array
    {
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/me/adaccounts", [
            'access_token' => $accessToken,
            'fields' => 'id,name,account_id,account_status,currency,timezone_name',
            'limit' => 100,
        ]);
        
        if ($response->successful() && isset($response->json()['data'])) {
            return $response->json()['data'];
        }
        
        return [];
    }

    /**
     * Obtener Pages del usuario
     */
    protected function getPages(string $accessToken): array
    {
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/me/accounts", [
            'access_token' => $accessToken,
            'fields' => 'id,name,category,access_token',
            'limit' => 100,
        ]);
        
        if ($response->successful() && isset($response->json()['data'])) {
            // No guardar access_token de páginas en el listado (solo cuando se necesite)
            return collect($response->json()['data'])->map(function ($page) {
                return [
                    'id' => $page['id'],
                    'name' => $page['name'],
                    'category' => $page['category'] ?? null,
                ];
            })->toArray();
        }
        
        return [];
    }

    /**
     * Crear o actualizar conexión de Facebook
     */
    protected function createOrUpdateConnection(
        ?User $user,
        array $facebookUser,
        string $accessToken,
        int $expiresIn,
        array $adAccounts,
        array $pages
    ): UserFacebookConnection {
        
        $data = [
            'facebook_user_id' => $facebookUser['id'],
            'facebook_name' => $facebookUser['name'] ?? null,
            'facebook_email' => $facebookUser['email'] ?? null,
            'access_token' => $accessToken,
            'token_expires_at' => now()->addSeconds($expiresIn),
            'scopes' => $this->requiredScopes,
            'ad_accounts' => $adAccounts,
            'pages' => $pages,
            'is_active' => true,
            'last_used_at' => now(),
        ];
        
        if ($user) {
            $data['user_id'] = $user->id;
            
            return UserFacebookConnection::updateOrCreate(
                ['user_id' => $user->id],
                $data
            );
        }
        
        // Si no hay usuario autenticado, buscar por facebook_user_id
        return UserFacebookConnection::updateOrCreate(
            ['facebook_user_id' => $facebookUser['id']],
            $data
        );
    }
}
