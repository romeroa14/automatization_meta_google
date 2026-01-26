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
use Illuminate\Support\Facades\Hash;

class WhatsAppSignupController extends Controller
{
    protected string $graphApiVersion = 'v24.0';
    protected string $graphApiUrl = 'https://graph.facebook.com';
    
    /**
     * Obtener configuración para el frontend
     */
    public function getConfig()
    {
        $oauthAccount = FacebookAccount::getOAuthAccount();
        
        if (!$oauthAccount) {
            return response()->json([
                'success' => false,
                'error' => 'No hay cuenta de Facebook configurada en el sistema.',
            ], 500);
        }
        
        $configId = config('services.facebook.wa_signup_config_id');
        
        if (!$configId) {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp Signup no está configurado. Contacte al administrador.',
            ], 500);
        }
        
        return response()->json([
            'config_id' => $configId,
            'app_id' => $oauthAccount->app_id,
        ]);
    }
    
    /**
     * Manejar callback del WhatsApp Embedded Signup
     */
    public function handleCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $code = $request->input('code');
        
        try {
            Log::info('[WhatsApp Signup] Processing callback', ['code' => substr($code, 0, 20) . '...']);
            
            // 1. Intercambiar código por access token
            $tokenResponse = $this->exchangeCodeForToken($code);
            
            if (!$tokenResponse['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $tokenResponse['error'],
                ], 400);
            }
            
            $accessToken = $tokenResponse['access_token'];
            
            // 2. Obtener información del usuario de Facebook
            $userInfo = $this->getFacebookUserInfo($accessToken);
            
            if (!$userInfo['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error obteniendo información del usuario',
                ], 400);
            }
            
            // 3. Obtener información del WABA (WhatsApp Business Account)
            $wabaInfo = $this->getWABAInfo($accessToken);
            
            Log::info('[WhatsApp Signup] WABA Info obtained', [
                'user_id' => $userInfo['data']['id'],
                'waba_count' => count($wabaInfo),
            ]);
            
            // 4. Crear o actualizar usuario en nuestra BD
            $user = $this->createOrUpdateUser($userInfo['data'], $wabaInfo, $accessToken);
            
            // 5. Generar token de autenticación de Laravel
            $token = $user->createToken('whatsapp-signup')->plainTextToken;
            
            Log::info('[WhatsApp Signup] User registered/authenticated', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'message' => 'WhatsApp Business conectado exitosamente',
            ]);
            
        } catch (\Exception $e) {
            Log::error('[WhatsApp Signup] Error processing callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error procesando registro: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Webhook para account_update de Facebook
     */
    public function handleWebhookAccountUpdate(Request $request)
    {
        // Verificar que es una petición de Facebook
        $verifyToken = config('services.facebook.webhook_verify_token', 'admetricas_webhook_token');
        
        // Verificación de webhook (GET request de Facebook para configurar)
        if ($request->isMethod('get')) {
            if ($request->input('hub_mode') === 'subscribe' 
                && $request->input('hub_verify_token') === $verifyToken) {
                return response($request->input('hub_challenge'), 200);
            }
            return response('Forbidden', 403);
        }
        
        // Procesar webhook (POST request)
        $data = $request->all();
        
        Log::info('[WhatsApp Signup Webhook] Received account_update', $data);
        
        try {
            // Validar firma de Facebook (importante para seguridad)
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('[WhatsApp Signup Webhook] Invalid signature');
                return response('Forbidden', 403);
            }
            
            // Procesar cada entrada del webhook
            foreach ($data['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    if ($change['field'] === 'account_update') {
                        $this->processAccountUpdate($change['value']);
                    }
                }
            }
            
            return response()->json(['status' => 'ok'], 200);
            
        } catch (\Exception $e) {
            Log::error('[WhatsApp Signup Webhook] Error processing webhook', [
                'error' => $e->getMessage(),
            ]);
            
            // Retornar 200 para que Facebook no reintente
            return response()->json(['status' => 'error'], 200);
        }
    }
    
    /**
     * Intercambiar código por access token
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
        
        // Detectar redirect_uri según el entorno (producción vs desarrollo)
        // NOTA: Para WhatsApp Embedded Signup, NO enviamos redirect_uri
        // porque el código se devuelve vía JavaScript callback, no por redirect
        
        Log::info('[WhatsApp Signup] Exchanging code for token (Embedded Signup - no redirect_uri)', [
            'environment' => config('app.env'),
        ]);
        
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/oauth/access_token", [
            'client_id' => $oauthAccount->app_id,
            'client_secret' => $oauthAccount->app_secret,
            // NO incluir redirect_uri para Embedded Signup
            'code' => $code,
        ]);
        
        if ($response->successful() && isset($response->json()['access_token'])) {
            Log::info('[WhatsApp Signup] Token exchange successful');
            return [
                'success' => true,
                'access_token' => $response->json()['access_token'],
            ];
        }
        
        Log::error('[WhatsApp Signup] Token exchange failed', [
            'response' => $response->json(),
            'status' => $response->status(),
        ]);
        
        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Error desconocido al intercambiar token',
        ];
    }
    
    /**
     * Obtener redirect_uri según el entorno
     */
    protected function getRedirectUri(): string
    {
        // Si hay una variable de entorno específica, usarla
        $envRedirectUri = config('services.facebook.redirect_uri');
        if ($envRedirectUri) {
            Log::info('[WhatsApp Signup] Using redirect_uri from .env', ['uri' => $envRedirectUri]);
            return $envRedirectUri;
        }
        
        // Detectar según el entorno de la aplicación
        $appEnv = config('app.env');
        $appUrl = config('app.url');
        
        // Si estamos en producción
        if ($appEnv === 'production' || str_contains($appUrl, 'admetricas.com')) {
            $uri = 'https://app.admetricas.com/auth/facebook/callback';
            Log::info('[WhatsApp Signup] Using production redirect_uri', ['uri' => $uri]);
            return $uri;
        }
        
        // Desarrollo: localhost con HTTPS
        $uri = 'https://localhost:9000/auth/facebook/callback';
        Log::info('[WhatsApp Signup] Using development redirect_uri', ['uri' => $uri]);
        return $uri;
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
     * Obtener información de WhatsApp Business Accounts
     */
    protected function getWABAInfo(string $accessToken): array
    {
        $response = Http::get("{$this->graphApiUrl}/{$this->graphApiVersion}/me/businesses", [
            'access_token' => $accessToken,
            'fields' => 'id,name,owned_whatsapp_business_accounts{id,name,message_template_namespace,account_review_status,owner_business_info}',
        ]);
        
        if (!$response->successful()) {
            Log::warning('[WhatsApp Signup] Could not get WABA info', [
                'response' => $response->json(),
            ]);
            return [];
        }
        
        $businesses = $response->json()['data'] ?? [];
        $wabas = [];
        
        foreach ($businesses as $business) {
            foreach ($business['owned_whatsapp_business_accounts']['data'] ?? [] as $waba) {
                $wabas[] = [
                    'waba_id' => $waba['id'],
                    'waba_name' => $waba['name'] ?? null,
                    'business_id' => $business['id'],
                    'business_name' => $business['name'] ?? null,
                    'namespace' => $waba['message_template_namespace'] ?? null,
                    'review_status' => $waba['account_review_status'] ?? null,
                ];
            }
        }
        
        return $wabas;
    }
    
    /**
     * Crear o actualizar usuario con información de WhatsApp
     */
    protected function createOrUpdateUser(array $facebookUser, array $wabaInfo, string $accessToken): User
    {
        $facebookUserId = $facebookUser['id'];
        $email = $facebookUser['email'] ?? null;
        $name = $facebookUser['name'] ?? 'Usuario WhatsApp';
        
        // Si no tiene email, crear uno temporal basado en el Facebook User ID
        if (!$email) {
            $email = "fb_{$facebookUserId}@admetricas.temp";
        }
        
        // Buscar usuario existente por email o por Facebook User ID en connections
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Crear nuevo usuario
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)), // Password aleatorio
                'email_verified_at' => now(), // Auto-verificar porque viene de Facebook
            ]);
            
            Log::info('[WhatsApp Signup] New user created', [
                'user_id' => $user->id,
                'email' => $email,
            ]);
        } else {
            // Actualizar nombre si es necesario
            if ($user->name !== $name) {
                $user->update(['name' => $name]);
            }
            
            Log::info('[WhatsApp Signup] Existing user found', [
                'user_id' => $user->id,
                'email' => $email,
            ]);
        }
        
        // Crear o actualizar conexión de Facebook con datos de WhatsApp
        $connectionData = [
            'facebook_user_id' => $facebookUserId,
            'facebook_name' => $name,
            'facebook_email' => $facebookUser['email'] ?? null,
            'access_token' => $accessToken,
            'token_expires_at' => now()->addDays(60), // WhatsApp tokens duran 60 días
            'scopes' => ['whatsapp_business_management', 'whatsapp_business_messaging'],
            'is_active' => true,
            'last_used_at' => now(),
        ];
        
        // Agregar información de WABA si existe
        if (!empty($wabaInfo)) {
            $firstWaba = $wabaInfo[0];
            $connectionData['waba_id'] = $firstWaba['waba_id'];
            $connectionData['business_id'] = $firstWaba['business_id'];
            $connectionData['waba_data'] = $wabaInfo; // Guardar todos los WABAs como JSON
            $connectionData['signup_method'] = 'embedded_signup';
        }
        
        UserFacebookConnection::updateOrCreate(
            ['user_id' => $user->id],
            $connectionData
        );
        
        return $user;
    }
    
    /**
     * Verificar firma del webhook de Facebook
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }
        
        $oauthAccount = FacebookAccount::getOAuthAccount();
        if (!$oauthAccount) {
            return false;
        }
        
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $oauthAccount->app_secret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Procesar actualización de cuenta desde webhook
     */
    protected function processAccountUpdate(array $data): void
    {
        Log::info('[WhatsApp Signup] Processing account update', $data);
        
        // Aquí puedes procesar actualizaciones específicas
        // Por ejemplo, cuando un WABA cambia de estado, se agrega un número, etc.
        
        // Ejemplo: actualizar información del WABA si existe en nuestra BD
        if (isset($data['waba_id'])) {
            $connection = UserFacebookConnection::where('waba_id', $data['waba_id'])->first();
            
            if ($connection) {
                // Actualizar datos relevantes
                $connection->update([
                    'last_used_at' => now(),
                    // Agregar más campos según sea necesario
                ]);
                
                Log::info('[WhatsApp Signup] WABA connection updated', [
                    'waba_id' => $data['waba_id'],
                    'user_id' => $connection->user_id,
                ]);
            }
        }
    }
}
