<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount;

class MetaApiService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';
    protected int $rateLimitDelay = 2; // Segundos entre llamadas
    protected int $maxRetries = 3; // Máximo de reintentos

    /**
     * Realizar llamada HTTP con rate limiting y retry logic
     */
    protected function makeApiCall(string $url, array $params = [], string $method = 'GET'): ?array
    {
        $retryCount = 0;
        
        while ($retryCount < $this->maxRetries) {
            try {
                // Rate limiting: esperar antes de cada llamada
                if ($retryCount > 0) {
                    $delay = $this->rateLimitDelay * ($retryCount + 1);
                    Log::info("⏳ Esperando {$delay}s antes del reintento #{$retryCount}");
                    sleep($delay);
                } else {
                    sleep($this->rateLimitDelay);
                }

                $response = Http::timeout(60)->$method($url, $params);

                if ($response->successful()) {
                    return $response->json();
                }

                // Verificar si es error de rate limit
                if ($response->status() === 400) {
                    $errorData = $response->json();
                    if (isset($errorData['error']['code']) && $errorData['error']['code'] === 17) {
                        Log::warning("⚠️ Rate limit alcanzado, esperando más tiempo...", [
                            'retry_count' => $retryCount,
                            'error' => $errorData['error']['message'] ?? 'Rate limit exceeded'
                        ]);
                        
                        $retryCount++;
                        continue;
                    }
                }

                // Si no es rate limit, logear y retornar null
                Log::error("❌ Error en llamada API", [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;

            } catch (\Exception $e) {
                Log::error("❌ Excepción en llamada API", [
                    'url' => $url,
                    'retry_count' => $retryCount,
                    'error' => $e->getMessage()
                ]);

                $retryCount++;
            }
        }

        Log::error("❌ Máximo de reintentos alcanzado", [
            'url' => $url,
            'max_retries' => $this->maxRetries
        ]);

        return null;
    }

    /**
     * Obtener cuentas publicitarias de Meta
     */
    public function getAdAccounts(FacebookAccount $facebookAccount): array
    {
        try {
            $response = $this->makeApiCall("{$this->baseUrl}/me/adaccounts", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,account_status,currency,timezone_name,amount_spent,balance',
                'limit' => 100
            ]);

            if ($response && isset($response['data'])) {
                Log::info('✅ Cuentas publicitarias obtenidas', [
                    'facebook_account_id' => $facebookAccount->id,
                    'count' => count($response['data'])
                ]);
                
                return $this->formatAdAccounts($response['data']);
            }

            Log::error('❌ Error obteniendo cuentas publicitarias', [
                'facebook_account_id' => $facebookAccount->id
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('❌ Excepción obteniendo cuentas publicitarias', [
                'facebook_account_id' => $facebookAccount->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Obtener fanpages de Meta
     */
    public function getPages(FacebookAccount $facebookAccount): array
    {
        try {
            $response = $this->makeApiCall("{$this->baseUrl}/me/accounts", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,category,access_token,tasks,instagram_business_account',
                'limit' => 1000
            ]);

            if ($response && isset($response['data'])) {
                Log::info('✅ Fanpages obtenidas', [
                    'facebook_account_id' => $facebookAccount->id,
                    'count' => count($response['data'])
                ]);
                
                return $this->formatPages($response['data']);
            }

            Log::error('❌ Error obteniendo fanpages', [
                'facebook_account_id' => $facebookAccount->id
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error('❌ Excepción obteniendo fanpages', [
                'facebook_account_id' => $facebookAccount->id,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Obtener objetivos de campaña disponibles
     */
    public function getCampaignObjectives(): array
    {
        return [
            'OUTCOME_TRAFFIC' => 'Tráfico al sitio web',
            'OUTCOME_ENGAGEMENT' => 'Compromiso',
            'OUTCOME_AWARENESS' => 'Conciencia de marca',
            'OUTCOME_LEADS' => 'Generación de leads',
            'OUTCOME_SALES' => 'Ventas',
            'OUTCOME_APP_PROMOTION' => 'Promoción de app',
            'MESSAGES' => 'Mensajes',
            'CONVERSIONS' => 'Conversiones',
            'TRAFFIC' => 'Tráfico',
            'REACH' => 'Alcance',
            'BRAND_AWARENESS' => 'Conciencia de marca',
            'ENGAGEMENT' => 'Compromiso',
            'LEAD_GENERATION' => 'Generación de leads',
            'SALES' => 'Ventas',
            'APP_INSTALLS' => 'Instalaciones de app',
            'VIDEO_VIEWS' => 'Visualizaciones de video'
        ];
    }

    /**
     * Validar token de acceso
     */
    public function validateAccessToken(FacebookAccount $facebookAccount): bool
    {
        try {
            $response = $this->makeApiCall("{$this->baseUrl}/me", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name'
            ]);

            if ($response && isset($response['id'])) {
                Log::info('✅ Token de acceso válido', [
                    'facebook_account_id' => $facebookAccount->id,
                    'user_id' => $response['id'],
                    'user_name' => $response['name'] ?? 'N/A'
                ]);
                
                return true;
            }

            Log::error('❌ Token de acceso inválido', [
                'facebook_account_id' => $facebookAccount->id
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('❌ Excepción validando token', [
                'facebook_account_id' => $facebookAccount->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Formatear cuentas publicitarias para mostrar
     */
    private function formatAdAccounts(array $accounts): array
    {
        $formatted = [];
        
        foreach ($accounts as $account) {
            $formatted[] = [
                'id' => $account['id'],
                'name' => $account['name'],
                'currency' => $account['currency'] ?? 'USD',
                'status' => $account['account_status'] ?? 'unknown',
                'timezone' => $account['timezone_name'] ?? 'UTC',
                'amount_spent' => $account['amount_spent'] ?? 0,
                'balance' => $account['balance'] ?? 0,
                'display_name' => "{$account['name']} - {$account['id']}"
            ];
        }
        
        return $formatted;
    }

    /**
     * Formatear fanpages para mostrar
     */
    private function formatPages(array $pages): array
    {
        $formatted = [];
        
        foreach ($pages as $page) {
            $formatted[] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'category' => $page['category'] ?? 'Business',
                'access_token' => $page['access_token'] ?? null,
                'tasks' => $page['tasks'] ?? [],
                'display_name' => "{$page['name']} - {$page['id']}",
                'has_instagram' => isset($page['instagram_business_account']),
                'instagram_id' => $page['instagram_business_account']['id'] ?? null
            ];
        }
        
        return $formatted;
    }

    /**
     * Obtener información de cuenta de Instagram
     */
    public function getInstagramAccountInfo(string $instagramId, string $pageAccessToken): ?array
    {
        try {
            $response = $this->makeApiCall("{$this->baseUrl}/{$instagramId}", [
                'access_token' => $pageAccessToken,
                'fields' => 'id,username,name,biography,followers_count,follows_count,media_count,profile_picture_url'
            ]);

            if ($response) {
                return [
                    'id' => $response['id'] ?? $instagramId,
                    'username' => $response['username'] ?? 'N/A',
                    'name' => $response['name'] ?? $response['username'] ?? 'Instagram Account',
                    'biography' => $response['biography'] ?? '',
                    'followers_count' => $response['followers_count'] ?? 0,
                    'follows_count' => $response['follows_count'] ?? 0,
                    'media_count' => $response['media_count'] ?? 0,
                    'profile_picture_url' => $response['profile_picture_url'] ?? null
                ];
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo información de Instagram', [
                'instagram_id' => $instagramId,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Obtener información detallada de una cuenta publicitaria
     */
    public function getAdAccountDetails(string $adAccountId, FacebookAccount $facebookAccount): ?array
    {
        try {
            $response = $this->makeApiCall("{$this->baseUrl}/{$adAccountId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,account_status,currency,timezone_name,amount_spent,balance,spend_cap,created_time'
            ]);

            if ($response) {
                Log::info('✅ Detalles de cuenta publicitaria obtenidos', [
                    'ad_account_id' => $adAccountId,
                    'facebook_account_id' => $facebookAccount->id
                ]);
                
                return $response;
            }

            Log::error('❌ Error obteniendo detalles de cuenta publicitaria', [
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccount->id
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Excepción obteniendo detalles de cuenta publicitaria', [
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccount->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Obtener información detallada de una fanpage
     */
    public function getPageDetails(string $pageId, FacebookAccount $facebookAccount): ?array
    {
        try {
            $response = $this->makeApiCall("{$this->baseUrl}/{$pageId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,category,about,phone,website,emails,location,link,fan_count,verification_status'
            ]);

            if ($response) {
                Log::info('✅ Detalles de fanpage obtenidos', [
                    'page_id' => $pageId,
                    'facebook_account_id' => $facebookAccount->id
                ]);
                
                return $response;
            }

            Log::error('❌ Error obteniendo detalles de fanpage', [
                'page_id' => $pageId,
                'facebook_account_id' => $facebookAccount->id
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Excepción obteniendo detalles de fanpage', [
                'page_id' => $pageId,
                'facebook_account_id' => $facebookAccount->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Obtener el saldo de fondos prepagados de una cuenta publicitaria
     */
    public function getAccountBalance($adAccountId, $facebookAccountId = null)
    {
        try {
            $facebookAccount = $facebookAccountId 
                ? FacebookAccount::find($facebookAccountId)
                : FacebookAccount::where('is_active', true)->first();

            if (!$facebookAccount) {
                throw new \Exception('No se encontró cuenta de Facebook activa');
            }

            $response = $this->makeApiCall("{$this->baseUrl}/{$adAccountId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'balance,currency,account_status,name,amount_spent'
            ]);
            
            if (!$response) {
                throw new \Exception('Error en la llamada a la API');
            }

            return [
                'success' => true,
                'data' => $response,
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccount->id
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo saldo de cuenta publicitaria', [
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccountId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'ad_account_id' => $adAccountId
            ];
        }
    }

    /**
     * Obtener información detallada de la cuenta publicitaria incluyendo saldo
     */
    public function getAccountInfo($adAccountId, $facebookAccountId = null)
    {
        try {
            $facebookAccount = $facebookAccountId 
                ? FacebookAccount::find($facebookAccountId)
                : FacebookAccount::where('is_active', true)->first();

            if (!$facebookAccount) {
                throw new \Exception('No se encontró cuenta de Facebook activa');
            }

            $response = $this->makeApiCall("{$this->baseUrl}/{$adAccountId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'balance,currency,account_status,name,amount_spent'
            ]);
            
            if (!$response) {
                throw new \Exception('Error obteniendo información de cuenta');
            }

            // Extraer información de saldo de la respuesta
            $balanceData = [
                'amount' => $response['balance'] ?? null,
                'currency' => $response['currency'] ?? null,
                'account_status' => $response['account_status'] ?? null,
                'amount_spent' => $response['amount_spent'] ?? null
            ];

            return [
                'success' => true,
                'data' => [
                    'account_info' => $response,
                    'balance' => $balanceData
                ],
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccount->id
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo información de cuenta publicitaria', [
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccountId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'ad_account_id' => $adAccountId
            ];
        }
    }
}