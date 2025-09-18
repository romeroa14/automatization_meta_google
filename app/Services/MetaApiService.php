<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount;

class MetaApiService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';

    /**
     * Obtener cuentas publicitarias de Meta
     */
    public function getAdAccounts(FacebookAccount $facebookAccount): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/me/adaccounts", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,account_status,currency,timezone_name,amount_spent,balance',
                'limit' => 100
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'])) {
                    Log::info('✅ Cuentas publicitarias obtenidas', [
                        'facebook_account_id' => $facebookAccount->id,
                        'count' => count($data['data'])
                    ]);
                    
                    return $this->formatAdAccounts($data['data']);
                }
            }

            Log::error('❌ Error obteniendo cuentas publicitarias', [
                'facebook_account_id' => $facebookAccount->id,
                'status' => $response->status(),
                'response' => $response->body()
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
            $response = Http::get("{$this->baseUrl}/me/accounts", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,category,access_token,tasks,instagram_business_account',
                'limit' => 1000  // Aumentar límite para obtener todas las fanpages
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'])) {
                    Log::info('✅ Fanpages obtenidas', [
                        'facebook_account_id' => $facebookAccount->id,
                        'count' => count($data['data'])
                    ]);
                    
                    return $this->formatPages($data['data']);
                }
            }

            Log::error('❌ Error obteniendo fanpages', [
                'facebook_account_id' => $facebookAccount->id,
                'status' => $response->status(),
                'response' => $response->body()
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
            $response = Http::get("{$this->baseUrl}/me", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['id'])) {
                    Log::info('✅ Token de acceso válido', [
                        'facebook_account_id' => $facebookAccount->id,
                        'user_id' => $data['id'],
                        'user_name' => $data['name'] ?? 'N/A'
                    ]);
                    
                    return true;
                }
            }

            Log::error('❌ Token de acceso inválido', [
                'facebook_account_id' => $facebookAccount->id,
                'status' => $response->status(),
                'response' => $response->body()
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
            $instagramInfo = null;
            
            // Verificar si tiene cuenta de Instagram conectada
            if (isset($page['instagram_business_account'])) {
                $instagramInfo = $this->getInstagramAccountInfo($page['instagram_business_account']['id'], $page['access_token']);
            }
            
            $formatted[] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'category' => $page['category'] ?? 'Business',
                'access_token' => $page['access_token'] ?? null,
                'tasks' => $page['tasks'] ?? [],
                'display_name' => "{$page['name']} - {$page['id']}",
                'instagram_account' => $instagramInfo
            ];
        }
        
        return $formatted;
    }

    /**
     * Obtener información de cuenta de Instagram
     */
    private function getInstagramAccountInfo(string $instagramId, string $pageAccessToken): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/{$instagramId}", [
                'access_token' => $pageAccessToken,
                'fields' => 'id,username,name,biography,followers_count,follows_count,media_count,profile_picture_url'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'id' => $data['id'],
                    'username' => $data['username'],
                    'name' => $data['name'],
                    'biography' => $data['biography'] ?? '',
                    'followers_count' => $data['followers_count'] ?? 0,
                    'follows_count' => $data['follows_count'] ?? 0,
                    'media_count' => $data['media_count'] ?? 0,
                    'profile_picture_url' => $data['profile_picture_url'] ?? null
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
            $response = Http::get("{$this->baseUrl}/{$adAccountId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,account_status,currency,timezone_name,amount_spent,balance,spend_cap,created_time'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('✅ Detalles de cuenta publicitaria obtenidos', [
                    'ad_account_id' => $adAccountId,
                    'facebook_account_id' => $facebookAccount->id
                ]);
                
                return $data;
            }

            Log::error('❌ Error obteniendo detalles de cuenta publicitaria', [
                'ad_account_id' => $adAccountId,
                'facebook_account_id' => $facebookAccount->id,
                'status' => $response->status()
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
            $response = Http::get("{$this->baseUrl}/{$pageId}", [
                'access_token' => $facebookAccount->access_token,
                'fields' => 'id,name,category,about,phone,website,emails,location,link,fan_count,verification_status'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('✅ Detalles de fanpage obtenidos', [
                    'page_id' => $pageId,
                    'facebook_account_id' => $facebookAccount->id
                ]);
                
                return $data;
            }

            Log::error('❌ Error obteniendo detalles de fanpage', [
                'page_id' => $pageId,
                'facebook_account_id' => $facebookAccount->id,
                'status' => $response->status()
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
}
