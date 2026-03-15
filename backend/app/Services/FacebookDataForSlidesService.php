<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportCampaign;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Log;

class FacebookDataForSlidesService
{
    /**
     * Obtiene datos reales de Facebook para un reporte
     */
    public function getFacebookDataForReport(Report $report): array
    {
        $reportData = [
            'title' => $report->name,
            'subtitle' => $report->description ?? 'Reporte generado automáticamente',
            'period' => [
                'start' => $report->period_start?->format('d/m/Y'),
                'end' => $report->period_end?->format('d/m/Y'),
            ],
            'brands' => [],
            'statistics' => [],
            'charts' => [],
        ];

        // Obtener datos de cada marca
        foreach ($report->brands as $brand) {
            $brandData = $this->getBrandData($brand, $report);
            $reportData['brands'][] = $brandData;
        }

        // Calcular estadísticas generales
        $reportData['statistics'] = $this->calculateGeneralStatistics($report);

        return $reportData;
    }

    /**
     * Obtiene datos de una marca específica
     */
    private function getBrandData($brand, Report $report): array
    {
        $campaigns = $report->campaigns()
            ->where('report_brand_id', $brand->id)
            ->get();

        $brandData = [
            'name' => $brand->brand_name,
            'ads' => [],
            'statistics' => [],
        ];

        // Obtener datos de cada campaña y sus anuncios
        foreach ($campaigns as $campaign) {
            $adsData = $this->getCampaignAdsData($campaign);
            $brandData['ads'] = array_merge($brandData['ads'], $adsData);
        }

        // Calcular estadísticas de la marca
        $brandData['statistics'] = $this->calculateBrandStatistics($campaigns);

        return $brandData;
    }

    /**
     * Obtiene datos de una campaña específica
     */
    private function getCampaignData(ReportCampaign $campaign): array
    {
        // Si ya tenemos datos en la base de datos, los usamos
        if ($campaign->statistics && is_array($campaign->statistics)) {
            return [
                'name' => $campaign->campaign_name,
                'id' => $campaign->campaign_id,
                'statistics' => $campaign->statistics,
                'image_url' => $campaign->ad_image_url,
                'image_local_path' => $campaign->ad_image_local_path,
            ];
        }

        // Si no, obtenemos datos frescos de Facebook
        return $this->getFreshCampaignData($campaign);
    }

    /**
     * Obtiene datos de los anuncios de una campaña específica
     */
    private function getCampaignAdsData(ReportCampaign $campaign): array
    {
        try {
            // Configurar Facebook API
            $facebookAccount = $this->getFacebookAccountForCampaign($campaign);
            
            if (!$facebookAccount) {
                Log::warning("No se encontró cuenta de Facebook para campaña: {$campaign->campaign_id}");
                return [];
            }

            Api::init(
                $facebookAccount->app_id,
                $facebookAccount->app_secret,
                $facebookAccount->access_token
            );

            $adAccountId = $facebookAccount->selected_ad_account_id ?: $facebookAccount->account_id;
            $account = new AdAccount('act_' . $adAccountId);

            // Obtener anuncios de la campaña
            $ads = $this->getCampaignAds($account, $campaign->campaign_id);
            
            return $ads;

        } catch (\Exception $e) {
            Log::error("Error obteniendo anuncios para campaña {$campaign->campaign_id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene datos frescos de Facebook para una campaña
     */
    private function getFreshCampaignData(ReportCampaign $campaign): array
    {
        try {
            // Configurar Facebook API
            $facebookAccount = $this->getFacebookAccountForCampaign($campaign);
            
            if (!$facebookAccount) {
                Log::warning("No se encontró cuenta de Facebook para campaña: {$campaign->campaign_id}");
                return $this->getEmptyCampaignData($campaign);
            }

            Api::init(
                $facebookAccount->app_id,
                $facebookAccount->app_secret,
                $facebookAccount->access_token
            );

            $adAccountId = $facebookAccount->selected_ad_account_id ?: $facebookAccount->account_id;
            $account = new AdAccount('act_' . $adAccountId);

            // Obtener insights de la campaña
            $insights = $this->getCampaignInsights($account, $campaign->campaign_id);
            
            // Procesar datos
            $statistics = $this->processCampaignInsights($insights);
            
            // Obtener imagen del anuncio
            $adImage = $this->getCampaignAdImage($campaign->campaign_id);

            return [
                'name' => $campaign->campaign_name,
                'id' => $campaign->campaign_id,
                'statistics' => $statistics,
                'image_url' => $adImage['url'],
                'image_local_path' => $adImage['local_path'],
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo datos frescos para campaña {$campaign->campaign_id}: " . $e->getMessage());
            return $this->getEmptyCampaignData($campaign);
        }
    }

    /**
     * Obtiene insights de una campaña específica
     */
    private function getCampaignInsights(AdAccount $account, string $campaignId): array
    {
        $fields = [
            'campaign_id',
            'campaign_name',
            'impressions',
            'clicks',
            'reach',
            'frequency',
            'ctr',
            'cpm',
            'cpc',
            'actions',
            'action_values',
            'video_p25_watched_actions',
            'video_p50_watched_actions',
            'video_p75_watched_actions',
            'video_p100_watched_actions',
            'inline_link_clicks',
            'unique_clicks',
            'unique_inline_link_clicks',
            'unique_actions',
        ];

        $params = [
            'level' => 'campaign',
            'limit' => 250,
            'time_range' => [
                'since' => date('Y-m-d', strtotime('-7 days')),
                'until' => date('Y-m-d'),
            ],
            'filtering' => [
                [
                    'field' => 'campaign.id',
                    'operator' => 'EQ',
                    'value' => $campaignId,
                ],
            ],
        ];

        try {
            $insights = $account->getInsights($fields, $params);
            return $insights->getData();
        } catch (\Exception $e) {
            Log::error("Error obteniendo insights para campaña {$campaignId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene anuncios de una campaña específica
     */
    private function getCampaignAds(AdAccount $account, string $campaignId): array
    {
        $fields = [
            'ad_id',
            'ad_name',
            'adset_id',
            'campaign_id',
            'impressions',
            'clicks',
            'spend',
            'reach',
            'frequency',
            'ctr',
            'cpm',
            'cpc',
            'actions',
            'action_values',
            'video_p25_watched_actions',
            'video_p50_watched_actions',
            'video_p75_watched_actions',
            'video_p100_watched_actions',
            'inline_link_clicks',
            'unique_clicks',
            'unique_inline_link_clicks',
            'unique_actions',
            'cost_per_action_type',
            'cost_per_unique_action_type',
        ];

        $params = [
            'level' => 'ad',
            'limit' => 250,
            'time_range' => [
                'since' => date('Y-m-d', strtotime('-7 days')),
                'until' => date('Y-m-d'),
            ],
            'filtering' => [
                [
                    'field' => 'campaign.id',
                    'operator' => 'EQ',
                    'value' => $campaignId,
                ],
            ],
        ];

        try {
            $insights = $account->getInsights($fields, $params);
            $adsData = [];
            foreach ($insights as $insight) {
                $adsData[] = $insight;
            }
            
            // Obtener creativos de los anuncios (simplificado por ahora)
            $adIds = collect($adsData)->pluck('ad_id')->filter()->toArray();
            $creatives = [];
            
            // Procesar cada anuncio
            return collect($adsData)->map(function ($insight) use ($creatives) {
                $creative = $creatives[$insight->ad_id ?? ''] ?? null;
                
                return [
                    'name' => $insight->ad_name ?? 'Sin nombre',
                    'id' => $insight->ad_id ?? null,
                    'campaign_id' => $insight->campaign_id ?? null,
                    'statistics' => [
                        'impressions' => (int)($insight->impressions ?? 0),
                        'clicks' => (int)($insight->clicks ?? 0),
                        'spend' => (float)($insight->spend ?? 0),
                        'reach' => (int)($insight->reach ?? 0),
                        'frequency' => (float)($insight->frequency ?? 0),
                        'ctr' => (float)($insight->ctr ?? 0),
                        'cpm' => (float)($insight->cpm ?? 0),
                        'cpc' => (float)($insight->cpc ?? 0),
                        'inline_link_clicks' => (int)($insight->inline_link_clicks ?? 0),
                        'unique_clicks' => (int)($insight->unique_clicks ?? 0),
                    ],
                    'image_url' => $creative['image_url'] ?? null,
                    'image_local_path' => $creative['local_path'] ?? null,
                ];
            })->toArray();
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo anuncios para campaña {$campaignId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Procesa los insights de una campaña
     */
    private function processCampaignInsights(array $insights): array
    {
        if (empty($insights)) {
            return $this->getEmptyStatistics();
        }

        $insight = $insights[0]; // Tomamos el primer insight

        // Procesar interacciones
        $actions = $insight['actions'] ?? [];
        $interactions = $this->processInteractions($actions);
        
        // Procesar videos vistos
        $videoViews = $this->processVideoViews($insight);

        return [
            'impressions' => (int)($insight['impressions'] ?? 0),
            'clicks' => (int)($insight['clicks'] ?? 0),
            'reach' => (int)($insight['reach'] ?? 0),
            'frequency' => (float)($insight['frequency'] ?? 0),
            'ctr' => (float)($insight['ctr'] ?? 0),
            'cpm' => (float)($insight['cpm'] ?? 0),
            'cpc' => (float)($insight['cpc'] ?? 0),
            'inline_link_clicks' => (int)($insight['inline_link_clicks'] ?? 0),
            'unique_clicks' => (int)($insight['unique_clicks'] ?? 0),
            'interactions' => $interactions,
            'total_interactions' => array_sum(array_column($interactions, 'value')),
            'interaction_rate' => $this->calculateInteractionRate($insight['impressions'] ?? 0, $interactions),
            'video_views' => $videoViews,
            'video_completion_rate' => $this->calculateVideoCompletionRate($videoViews),
        ];
    }

    /**
     * Procesa las interacciones de una campaña
     */
    private function processInteractions($actions): array
    {
        $interactions = [];
        
        if (is_array($actions)) {
            foreach ($actions as $action) {
                if (isset($action['action_type']) && isset($action['value'])) {
                    $interactions[] = [
                        'type' => $action['action_type'],
                        'value' => (int)$action['value'],
                        'label' => $this->getInteractionLabel($action['action_type'])
                    ];
                }
            }
        }
        
        return $interactions;
    }
    
    /**
     * Procesa las vistas de video
     */
    private function processVideoViews($insight): array
    {
        return [
            'p25' => (int)($insight['video_p25_watched_actions'] ?? 0),
            'p50' => (int)($insight['video_p50_watched_actions'] ?? 0),
            'p75' => (int)($insight['video_p75_watched_actions'] ?? 0),
            'p100' => (int)($insight['video_p100_watched_actions'] ?? 0),
        ];
    }
    
    /**
     * Calcula la tasa de interacción
     */
    private function calculateInteractionRate($impressions, $interactions): float
    {
        if ($impressions <= 0) return 0;
        
        $totalInteractions = array_sum(array_column($interactions, 'value'));
        return ($totalInteractions / $impressions) * 100;
    }
    
    /**
     * Calcula la tasa de finalización de video
     */
    private function calculateVideoCompletionRate($videoViews): float
    {
        $p100 = $videoViews['p100'] ?? 0;
        $p25 = $videoViews['p25'] ?? 0;
        
        if ($p25 <= 0) return 0;
        
        return ($p100 / $p25) * 100;
    }
    
    /**
     * Obtiene la etiqueta legible para un tipo de interacción
     */
    private function getInteractionLabel($actionType): string
    {
        return match($actionType) {
            'post_reaction' => 'Reacciones',
            'post_comment' => 'Comentarios',
            'post_share' => 'Compartidos',
            'post_save' => 'Guardados',
            'link_click' => 'Clicks en enlace',
            'video_view' => 'Vistas de video',
            'page_engagement' => 'Engagement de página',
            'onsite_conversion.messaging_first_reply' => 'Primera respuesta',
            'onsite_conversion.messaging_conversation_started_7d' => 'Conversaciones iniciadas',
            default => ucfirst(str_replace('_', ' ', $actionType)),
        };
    }

    /**
     * Obtiene la imagen del anuncio de una campaña
     */
    private function getCampaignAdImage(string $campaignId): array
    {
        try {
            // Obtener un anuncio de la campaña
            $campaign = new \FacebookAds\Object\Campaign($campaignId);
            $ads = $campaign->getAds(['id', 'name', 'creative']);
            
            if (empty($ads)) {
                return ['url' => null, 'local_path' => null];
            }

            $ad = $ads[0]; // Tomamos el primer anuncio
            $creativeId = $ad->creative['id'] ?? null;
            
            if (!$creativeId) {
                return ['url' => null, 'local_path' => null];
            }

            $creative = new \FacebookAds\Object\AdCreative($creativeId);
            $creativeData = $creative->getSelf(['id', 'name', 'image_url', 'thumbnail_url']);
            
            $imageUrl = $creativeData->image_url ?? $creativeData->thumbnail_url ?? null;
            
            if (!$imageUrl) {
                return ['url' => null, 'local_path' => null];
            }

            // Descargar y guardar la imagen localmente
            $localPath = $this->downloadAndSaveImage($imageUrl, $campaignId);
            
            return [
                'url' => $imageUrl,
                'local_path' => $localPath,
            ];

        } catch (\Exception $e) {
            Log::warning("Error obteniendo imagen para campaña {$campaignId}: " . $e->getMessage());
            return ['url' => null, 'local_path' => null];
        }
    }

    /**
     * Descarga y guarda una imagen localmente
     */
    private function downloadAndSaveImage(string $imageUrl, string $campaignId): ?string
    {
        try {
            $filename = 'campaign_' . $campaignId . '_' . time() . '.jpg';
            $path = 'public/campaign-images/' . $filename;
            
            // Crear directorio si no existe
            if (!\Illuminate\Support\Facades\Storage::exists('public/campaign-images')) {
                \Illuminate\Support\Facades\Storage::makeDirectory('public/campaign-images');
            }
            
            // Descargar imagen
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                return null;
            }
            
            // Guardar imagen
            \Illuminate\Support\Facades\Storage::put($path, $imageContent);
            
            // Retornar ruta relativa para usar con asset()
            return 'storage/campaign-images/' . $filename;
            
        } catch (\Exception $e) {
            Log::warning("Error descargando imagen para campaña {$campaignId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene la cuenta de Facebook para una campaña
     */
    private function getFacebookAccountForCampaign(ReportCampaign $campaign)
    {
        // Buscar en las cuentas asociadas al reporte
        $report = $campaign->report;
        $facebookAccounts = $report->facebookAccounts;
        
        foreach ($facebookAccounts as $account) {
            // Verificar si la campaña pertenece a esta cuenta
            if ($account->selected_campaign_ids && in_array($campaign->campaign_id, $account->selected_campaign_ids)) {
                return $account;
            }
        }
        
        // Si no se encuentra, retornar la primera cuenta
        return $facebookAccounts->first();
    }

    /**
     * Calcula estadísticas de una marca
     */
    private function calculateBrandStatistics($campaigns): array
    {
        if ($campaigns->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $totalImpressions = $campaigns->sum(function($campaign) {
            return $campaign->statistics['impressions'] ?? 0;
        });
        
        $totalClicks = $campaigns->sum(function($campaign) {
            return $campaign->statistics['clicks'] ?? 0;
        });
        
        $totalReach = $campaigns->sum(function($campaign) {
            return $campaign->statistics['reach'] ?? 0;
        });
        
        $totalInteractions = $campaigns->sum(function($campaign) {
            return $campaign->statistics['total_interactions'] ?? 0;
        });

        return [
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'reach' => $totalReach,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'total_interactions' => $totalInteractions,
            'interaction_rate' => $totalImpressions > 0 ? ($totalInteractions / $totalImpressions) * 100 : 0,
            'campaigns_count' => $campaigns->count(),
        ];
    }

    /**
     * Calcula estadísticas generales del reporte
     */
    private function calculateGeneralStatistics(Report $report): array
    {
        $campaigns = $report->campaigns;
        
        if ($campaigns->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $totalImpressions = $campaigns->sum(function($campaign) {
            return $campaign->statistics['impressions'] ?? 0;
        });
        
        $totalClicks = $campaigns->sum(function($campaign) {
            return $campaign->statistics['clicks'] ?? 0;
        });
        
        $totalReach = $campaigns->sum(function($campaign) {
            return $campaign->statistics['reach'] ?? 0;
        });
        
        $totalInteractions = $campaigns->sum(function($campaign) {
            return $campaign->statistics['total_interactions'] ?? 0;
        });

        return [
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'reach' => $totalReach,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'total_interactions' => $totalInteractions,
            'interaction_rate' => $totalImpressions > 0 ? ($totalInteractions / $totalImpressions) * 100 : 0,
            'campaigns_count' => $campaigns->count(),
            'brands_count' => $report->brands->count(),
        ];
    }

    /**
     * Retorna datos vacíos para una campaña
     */
    private function getEmptyCampaignData(ReportCampaign $campaign): array
    {
        return [
            'name' => $campaign->campaign_name,
            'id' => $campaign->campaign_id,
            'statistics' => $this->getEmptyStatistics(),
            'image_url' => null,
            'image_local_path' => null,
        ];
    }

    /**
     * Retorna estadísticas vacías
     */
    private function getEmptyStatistics(): array
    {
        return [
            'impressions' => 0,
            'clicks' => 0,
            'reach' => 0,
            'frequency' => 0,
            'ctr' => 0,
            'cpm' => 0,
            'cpc' => 0,
            'inline_link_clicks' => 0,
            'unique_clicks' => 0,
            'interactions' => [],
            'total_interactions' => 0,
            'interaction_rate' => 0,
            'video_views' => ['p25' => 0, 'p50' => 0, 'p75' => 0, 'p100' => 0],
            'video_completion_rate' => 0,
        ];
    }
}
