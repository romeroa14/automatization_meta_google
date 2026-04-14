<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount;

class TokenRenewalService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';

    /**
     * Renovar token de larga duración
     */
    public function renewLongLivedToken(FacebookAccount $facebookAccount): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $facebookAccount->app_id,
                'client_secret' => $facebookAccount->app_secret,
                'fb_exchange_token' => $facebookAccount->access_token
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['access_token'])) {
                    // Actualizar el token en la base de datos
                    $facebookAccount->update([
                        'access_token' => $data['access_token'],
                        'token_expires_at' => now()->addDays(60) // 60 días
                    ]);

                    Log::info('✅ Token renovado exitosamente', [
                        'facebook_account_id' => $facebookAccount->id,
                        'expires_at' => $facebookAccount->token_expires_at
                    ]);

                    return [
                        'success' => true,
                        'access_token' => $data['access_token'],
                        'expires_in' => $data['expires_in'] ?? 5184000, // 60 días en segundos
                        'expires_at' => $facebookAccount->token_expires_at
                    ];
                }
            }

            Log::error('❌ Error renovando token', [
                'facebook_account_id' => $facebookAccount->id,
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Error renovando token: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('❌ Excepción renovando token', [
                'facebook_account_id' => $facebookAccount->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar si el token necesita renovación
     */
    public function needsRenewal(FacebookAccount $facebookAccount): bool
    {
        if (!$facebookAccount->token_expires_at) {
            return true; // Si no hay fecha de expiración, asumir que necesita renovación
        }

        // Renovar si expira en los próximos 7 días
        return $facebookAccount->token_expires_at->isBefore(now()->addDays(7));
    }

    /**
     * Obtener token de página (sin expiración)
     */
    public function getPageToken(FacebookAccount $facebookAccount, string $pageId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/{$pageId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'access_token'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['access_token'])) {
                    Log::info('✅ Token de página obtenido', [
                        'facebook_account_id' => $facebookAccount->id,
                        'page_id' => $pageId
                    ]);

                    return [
                        'success' => true,
                        'page_token' => $data['access_token']
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Error obteniendo token de página: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('❌ Excepción obteniendo token de página', [
                'facebook_account_id' => $facebookAccount->id,
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Renovar todos los tokens que necesiten renovación
     */
    public function renewAllExpiredTokens(): array
    {
        $facebookAccounts = FacebookAccount::where('is_active', true)->get();
        $results = [];

        foreach ($facebookAccounts as $account) {
            if ($this->needsRenewal($account)) {
                $result = $this->renewLongLivedToken($account);
                $results[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->account_name,
                    'result' => $result
                ];
            }
        }

        return $results;
    }

    /**
     * Verificar estado del token
     */
    public function checkTokenStatus(FacebookAccount $facebookAccount): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/me", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'valid' => true,
                    'user_id' => $data['id'],
                    'user_name' => $data['name'],
                    'expires_at' => $facebookAccount->token_expires_at,
                    'needs_renewal' => $this->needsRenewal($facebookAccount)
                ];
            }

            return [
                'valid' => false,
                'error' => 'Token inválido o expirado'
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
