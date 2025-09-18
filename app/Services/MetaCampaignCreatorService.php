<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount;

class MetaCampaignCreatorService
{
    protected string $baseUrl = 'https://graph.facebook.com/v18.0';
    protected FacebookAccount $facebookAccount;
    protected array $campaignData = [];
    protected array $errors = [];
    protected array $warnings = [];
    protected bool $isDevelopmentMode = true;

    public function __construct(FacebookAccount $facebookAccount)
    {
        $this->facebookAccount = $facebookAccount;
        $this->isDevelopmentMode = $this->checkAppMode();
    }

    /**
     * Verificar si la app está en modo desarrollo
     */
    private function checkAppMode(): bool
    {
        try {
            $response = Http::get("https://graph.facebook.com/v18.0/{$this->facebookAccount->app_id}", [
                'access_token' => $this->facebookAccount->access_token,
                'fields' => 'id,name,app_type'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Si podemos obtener info de la app, probablemente esté en desarrollo
                return true;
            }
        } catch (\Exception $e) {
            Log::info('App en modo desarrollo detectado');
        }

        return true; // Por defecto asumimos desarrollo
    }

    /**
     * Crear campaña completa en Meta
     */
    public function createCampaign(array $data): array
    {
        $this->campaignData = $data;
        $this->errors = [];
        $this->warnings = [];

        Log::info('🚀 Iniciando creación de campaña en Meta', [
            'facebook_account_id' => $this->facebookAccount->id,
            'campaign_data' => $data,
            'is_development_mode' => $this->isDevelopmentMode
        ]);

        try {
            // Paso 1: Validar datos
            if (!$this->validateCampaignData()) {
                return $this->buildErrorResponse();
            }

            // Paso 2: Crear campaña
            $campaign = $this->createCampaignObject();
            if (!$campaign) {
                return $this->buildErrorResponse();
            }

            // Paso 3: Crear conjunto de anuncios
            $adSet = $this->createAdSet($campaign['id']);
            if (!$adSet) {
                return $this->buildErrorResponse();
            }

            // Paso 4: Crear anuncio (solo si no es modo desarrollo)
            if ($this->isDevelopmentMode) {
                $this->warnings[] = "App en modo desarrollo - Anuncio no creado (requiere app pública)";
                $ad = [
                    'id' => 'dev_mode_placeholder',
                    'name' => $this->campaignData['name'] . ' - Ad (Modo Desarrollo)',
                    'status' => 'DEVELOPMENT_MODE'
                ];
            } else {
                $ad = $this->createAd($adSet['id']);
                if (!$ad) {
                    return $this->buildErrorResponse();
                }
            }

            Log::info('✅ Campaña creada exitosamente', [
                'campaign_id' => $campaign['id'],
                'adset_id' => $adSet['id'],
                'ad_id' => $ad['id'],
                'is_development_mode' => $this->isDevelopmentMode
            ]);

            return [
                'success' => true,
                'campaign' => $campaign,
                'adset' => $adSet,
                'ad' => $ad,
                'warnings' => $this->warnings,
                'is_development_mode' => $this->isDevelopmentMode
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error creando campaña', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->errors
            ];
        }
    }

    /**
     * Validar datos de la campaña
     */
    private function validateCampaignData(): bool
    {
        $required = ['name', 'objective', 'ad_account_id', 'page_id', 'daily_budget'];
        
        foreach ($required as $field) {
            if (empty($this->campaignData[$field])) {
                $this->errors[] = "Campo requerido faltante: {$field}";
            }
        }

        // Validar objetivo (usar solo los objetivos válidos según Meta)
        $validObjectives = [
            'OUTCOME_LEADS', 'OUTCOME_SALES', 'OUTCOME_ENGAGEMENT', 
            'OUTCOME_AWARENESS', 'OUTCOME_TRAFFIC', 'OUTCOME_APP_PROMOTION'
        ];

        if (!in_array($this->campaignData['objective'], $validObjectives)) {
            $this->errors[] = "Objetivo de campaña no válido: {$this->campaignData['objective']}";
        }

        // Validar presupuesto (mínimo $1 USD)
        if (isset($this->campaignData['daily_budget']) && $this->campaignData['daily_budget'] < 1) {
            $this->errors[] = "El presupuesto diario debe ser al menos $1 USD";
        }

        // Validar targeting (construir desde geolocalización)
        $targeting = $this->buildTargeting();
        $this->validateTargeting($targeting);

        return empty($this->errors);
    }

    /**
     * Validar configuración de targeting
     */
    private function validateTargeting(array $targeting): void
    {
        // Validar geolocalización
        if (isset($targeting['geo_locations'])) {
            $this->validateGeoLocations($targeting['geo_locations']);
        }

        // Validar edad
        if (isset($targeting['age_min']) && isset($targeting['age_max'])) {
            if ($targeting['age_min'] < 13 || $targeting['age_max'] > 65) {
                $this->errors[] = "La edad debe estar entre 13 y 65 años";
            }
            if ($targeting['age_min'] > $targeting['age_max']) {
                $this->errors[] = "La edad mínima no puede ser mayor que la máxima";
            }
        }
    }

    /**
     * Validar geolocalización
     */
    private function validateGeoLocations(array $geoLocations): void
    {
        if (empty($geoLocations['countries']) && empty($geoLocations['regions']) && empty($geoLocations['cities'])) {
            $this->errors[] = "Debe especificar al menos una ubicación geográfica";
        }
    }

    /**
     * Crear objeto campaña
     */
    private function createCampaignObject(): ?array
    {
        $data = [
            'name' => $this->campaignData['name'],
            'objective' => $this->campaignData['objective'],
            'status' => 'PAUSED', // Siempre crear pausada por seguridad
            'special_ad_categories' => $this->campaignData['special_ad_categories'] ?? []
        ];

        $response = Http::post("{$this->baseUrl}/{$this->campaignData['ad_account_id']}/campaigns", [
            'access_token' => $this->facebookAccount->access_token,
            'name' => $data['name'],
            'objective' => $data['objective'],
            'status' => $data['status'],
            'special_ad_categories' => json_encode($data['special_ad_categories'])
        ]);

        if ($response->successful()) {
            $result = $response->json();
            if ($result['id']) {
                Log::info('✅ Campaña creada', ['campaign_id' => $result['id']]);
                return $result;
            }
        }

        $this->errors[] = "Error creando campaña: " . $response->body();
        Log::error('❌ Error creando campaña', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return null;
    }

    /**
     * Crear conjunto de anuncios
     */
    private function createAdSet(string $campaignId): ?array
    {
        $data = [
            'name' => $this->campaignData['name'] . ' - AdSet',
            'campaign_id' => $campaignId,
            'optimization_goal' => $this->getOptimizationGoal(),
            'billing_event' => $this->getBillingEvent(),
            'daily_budget' => $this->campaignData['daily_budget'] * 100, // Convertir a centavos
            'targeting' => $this->buildTargeting(),
            'status' => 'PAUSED',
            'bid_strategy' => $this->getBidStrategy(),
            'promoted_object' => $this->getPromotedObject()
        ];

        $requestData = [
            'access_token' => $this->facebookAccount->access_token,
            'name' => $data['name'],
            'campaign_id' => $data['campaign_id'],
            'optimization_goal' => $data['optimization_goal'],
            'billing_event' => $data['billing_event'],
            'daily_budget' => $data['daily_budget'],
            'targeting' => json_encode($data['targeting']),
            'status' => $data['status'],
            'bid_strategy' => $data['bid_strategy']
        ];
        
        // Solo incluir promoted_object si no es null
        if ($data['promoted_object'] !== null) {
            $requestData['promoted_object'] = json_encode($data['promoted_object']);
        }
        
        $response = Http::post("{$this->baseUrl}/{$this->campaignData['ad_account_id']}/adsets", $requestData);

        if ($response->successful()) {
            $result = $response->json();
            if ($result['id']) {
                Log::info('✅ Conjunto de anuncios creado', ['adset_id' => $result['id']]);
                return $result;
            }
        }

        $this->errors[] = "Error creando conjunto de anuncios: " . $response->body();
        Log::error('❌ Error creando conjunto de anuncios', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return null;
    }

    /**
     * Crear anuncio
     */
    private function createAd(string $adSetId): ?array
    {
        $data = [
            'name' => $this->campaignData['name'] . ' - Ad',
            'adset_id' => $adSetId,
            'creative' => $this->buildCreative(),
            'status' => 'PAUSED'
        ];

        $response = Http::post("{$this->baseUrl}/{$this->campaignData['ad_account_id']}/ads", [
            'access_token' => $this->facebookAccount->access_token,
            'name' => $data['name'],
            'adset_id' => $data['adset_id'],
            'creative' => json_encode($data['creative']),
            'status' => $data['status']
        ]);

        if ($response->successful()) {
            $result = $response->json();
            if ($result['id']) {
                Log::info('✅ Anuncio creado', ['ad_id' => $result['id']]);
                return $result;
            }
        }

        $this->errors[] = "Error creando anuncio: " . $response->body();
        Log::error('❌ Error creando anuncio', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return null;
    }

    /**
     * Obtener objetivo de optimización basado en el objetivo de campaña
     */
    private function getOptimizationGoal(): string
    {
        // Log para debuggear
        Log::info('🔍 Debug optimization_goal', [
            'objective' => $this->campaignData['objective'],
            'campaign_data' => $this->campaignData
        ]);
        
        // Mapeo específico para objetivos OUTCOME_
        $outcomeMapping = [
            'OUTCOME_TRAFFIC' => 'LINK_CLICKS',
            'OUTCOME_ENGAGEMENT' => 'POST_ENGAGEMENT', 
            'OUTCOME_AWARENESS' => 'REACH',
            'OUTCOME_LEADS' => 'LEAD_GENERATION',
            'OUTCOME_SALES' => 'OFFSITE_CONVERSIONS',
            'OUTCOME_APP_PROMOTION' => 'APP_INSTALLS'
        ];
        
        if (strpos($this->campaignData['objective'], 'OUTCOME_') === 0) {
            $result = $outcomeMapping[$this->campaignData['objective']] ?? 'REACH';
            Log::info('🎯 Using OUTCOME mapping', ['objective' => $this->campaignData['objective'], 'result' => $result]);
            return $result;
        }
        
        $mapping = [
            'TRAFFIC' => 'LINK_CLICKS',
            'ENGAGEMENT' => 'POST_ENGAGEMENT',
            'REACH' => 'REACH',
            'LEAD_GENERATION' => 'LEAD_GENERATION',
            'SALES' => 'OFFSITE_CONVERSIONS',
            'CONVERSION' => 'OFFSITE_CONVERSIONS', // CONVERSION es el objetivo válido
            'APP_INSTALLS' => 'APP_INSTALLS'
        ];

        $result = $mapping[$this->campaignData['objective']] ?? 'REACH';
        Log::info('🎯 Using standard mapping', ['objective' => $this->campaignData['objective'], 'result' => $result]);
        return $result;
    }

    /**
     * Obtener evento de facturación (usar solo valores válidos según Meta)
     */
    private function getBillingEvent(): string
    {
        // Mapeo específico para objetivos OUTCOME_
        $outcomeMapping = [
            'OUTCOME_TRAFFIC' => 'CLICKS',
            'OUTCOME_ENGAGEMENT' => 'IMPRESSIONS',
            'OUTCOME_AWARENESS' => 'IMPRESSIONS', 
            'OUTCOME_LEADS' => 'IMPRESSIONS',
            'OUTCOME_SALES' => 'IMPRESSIONS',
            'OUTCOME_APP_PROMOTION' => 'IMPRESSIONS'
        ];
        
        if (strpos($this->campaignData['objective'], 'OUTCOME_') === 0) {
            return $outcomeMapping[$this->campaignData['objective']] ?? 'IMPRESSIONS';
        }
        
        $mapping = [
            'TRAFFIC' => 'CLICKS',
            'ENGAGEMENT' => 'IMPRESSIONS',
            'REACH' => 'IMPRESSIONS',
            'LEAD_GENERATION' => 'IMPRESSIONS',
            'SALES' => 'IMPRESSIONS',
            'CONVERSION' => 'IMPRESSIONS', // CONVERSION es el objetivo válido
            'APP_INSTALLS' => 'IMPRESSIONS'
        ];

        return $mapping[$this->campaignData['objective']] ?? 'IMPRESSIONS';
    }

    /**
     * Obtener estrategia de puja
     */
    private function getBidStrategy(): string
    {
        $mapping = [
            'OUTCOME_TRAFFIC' => 'LOWEST_COST_WITHOUT_CAP',
            'OUTCOME_ENGAGEMENT' => 'LOWEST_COST_WITHOUT_CAP',
            'OUTCOME_AWARENESS' => 'LOWEST_COST_WITHOUT_CAP',
            'OUTCOME_LEADS' => 'LOWEST_COST_WITHOUT_CAP',
            'OUTCOME_SALES' => 'LOWEST_COST_WITHOUT_CAP',
            'OUTCOME_APP_PROMOTION' => 'LOWEST_COST_WITHOUT_CAP'
        ];

        return $mapping[$this->campaignData['objective']] ?? 'LOWEST_COST_WITHOUT_CAP';
    }

    /**
     * Obtener objeto promocionado para conversiones
     */
    private function getPromotedObject(): ?array
    {
        // Para objetivos de conversión, incluir promoted_object
        if (in_array($this->campaignData['objective'], ['CONVERSION', 'SALES', 'LEAD_GENERATION', 'OUTCOME_SALES', 'OUTCOME_LEADS'])) {
            return [
                'pixel_id' => $this->campaignData['pixel_id'] ?? $this->getDefaultPixelId(),
                'custom_event_type' => $this->getCustomEventType()
            ];
        }
        
        return null;
    }
    
    /**
     * Obtener pixel ID por defecto (usar el primer pixel disponible)
     */
    private function getDefaultPixelId(): ?string
    {
        try {
            $response = Http::get("{$this->baseUrl}/me/adspixels", [
                'access_token' => $this->facebookAccount->access_token
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']) && count($data['data']) > 0) {
                    return $data['data'][0]['id'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo obtener pixel ID', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Obtener tipo de evento personalizado basado en el objetivo
     */
    private function getCustomEventType(): string
    {
        $mapping = [
            'CONVERSION' => 'PURCHASE',
            'SALES' => 'PURCHASE', 
            'OUTCOME_SALES' => 'PURCHASE',
            'LEAD_GENERATION' => 'LEAD',
            'OUTCOME_LEADS' => 'LEAD'
        ];
        
        return $mapping[$this->campaignData['objective']] ?? 'PURCHASE';
    }

    /**
     * Construir configuración de targeting
     */
    private function buildTargeting(): array
    {
        $targeting = [
            'geo_locations' => $this->parseGeoLocations($this->campaignData['geolocation'] ?? 'VE'),
            'age_min' => $this->campaignData['age_min'] ?? 18,
            'age_max' => $this->campaignData['age_max'] ?? 65,
            'genders' => $this->campaignData['genders'] ?? [1, 2], // Ambos géneros por defecto
            'targeting_automation' => [
                'advantage_audience' => 1 // Habilitar Advantage Audience
            ]
        ];

        // Agregar intereses si están especificados
        if (!empty($this->campaignData['interests'])) {
            $targeting['interests'] = $this->campaignData['interests'];
        }

        return $targeting;
    }

    /**
     * Parsear geolocalización
     */
    private function parseGeoLocations(string $geolocation): array
    {
        $geo = ['countries' => [], 'regions' => [], 'cities' => []];

        // Parsear formato: VE, Caracas,VE, Miranda,VE, VE;CO
        $locations = explode(';', $geolocation);

        foreach ($locations as $location) {
            $location = trim($location);
            
            if (preg_match('/^[A-Z]{2}$/', $location)) {
                // Código de país (VE, US, ES)
                $geo['countries'][] = $location;
            } elseif (preg_match('/^([A-Za-z\s]+),([A-Z]{2})$/', $location, $matches)) {
                // Ciudad,País o Región,País
                $name = trim($matches[1]);
                $country = $matches[2];
                
                // Por ahora, agregamos como país
                // En una implementación más avanzada, podríamos distinguir entre ciudades y regiones
                $geo['countries'][] = $country;
            }
        }

        return $geo;
    }

    /**
     * Construir creativo
     */
    private function buildCreative(): array
    {
        return [
            'object_story_spec' => [
                'page_id' => $this->campaignData['page_id'],
                'link_data' => [
                    'message' => $this->campaignData['ad_copy'] ?? 'Mensaje del anuncio',
                    'link' => $this->campaignData['link'] ?? 'https://example.com',
                    'name' => $this->campaignData['ad_name'] ?? 'Título del anuncio',
                    'description' => $this->campaignData['description'] ?? 'Descripción del anuncio',
                    'call_to_action' => [
                        'type' => $this->getCallToActionType()
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtener tipo de llamada a la acción
     */
    private function getCallToActionType(): string
    {
        $mapping = [
            'OUTCOME_TRAFFIC' => 'LEARN_MORE',
            'OUTCOME_ENGAGEMENT' => 'LEARN_MORE',
            'OUTCOME_AWARENESS' => 'LEARN_MORE',
            'OUTCOME_LEADS' => 'SIGN_UP',
            'OUTCOME_SALES' => 'SHOP_NOW',
            'OUTCOME_APP_PROMOTION' => 'INSTALL_APP'
        ];

        return $mapping[$this->campaignData['objective']] ?? 'LEARN_MORE';
    }

    /**
     * Construir respuesta de error
     */
    private function buildErrorResponse(): array
    {
        return [
            'success' => false,
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    /**
     * Obtener información sobre el modo de la app
     */
    public function getAppModeInfo(): array
    {
        return [
            'is_development_mode' => $this->isDevelopmentMode,
            'can_create_ads' => !$this->isDevelopmentMode,
            'message' => $this->isDevelopmentMode 
                ? 'App en modo desarrollo - Solo se crean campañas y conjuntos de anuncios'
                : 'App en modo público - Se pueden crear anuncios completos'
        ];
    }
}