<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportBrand;
use App\Models\ReportCampaign;
use App\Models\FacebookAccount;
use App\Services\FacebookDataForSlidesService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleSlidesReportService
{
    protected string $webAppUrl;
    protected array $defaultSlideLayouts;
    protected FacebookDataForSlidesService $facebookDataService;

    public function __construct()
    {
        $this->webAppUrl = env('GOOGLE_WEBAPP_URL_slides');
        $this->facebookDataService = new FacebookDataForSlidesService();
        $this->defaultSlideLayouts = [
            'title' => 'TITLE_AND_SUBTITLE',
            'content' => 'TITLE_AND_BODY',
            'image' => 'TITLE_AND_BODY',
            'chart' => 'TITLE_AND_BODY',
        ];
    }

    /**
     * Genera un reporte completo en Google Slides (versión optimizada)
     */
    public function generateReport(Report $report): array
    {
        try {
            Log::info("🚀 Iniciando generación de reporte: {$report->name}");

            // Aumentar límites de tiempo para este proceso
            set_time_limit(300); // 5 minutos
            ini_set('max_execution_time', 300);

            // 1. Preparar datos del reporte (versión simplificada)
            $reportData = $this->prepareReportDataOptimized($report);
            
            // 2. Crear presentación en Google Slides
            $presentationId = $this->createPresentation($report);
            
            // 3. Generar diapositivas (versión optimizada)
            $this->generateSlidesOptimized($presentationId, $reportData);
            
            // 4. Obtener URL de la presentación
            $presentationUrl = $this->getPresentationUrl($presentationId);
            
            Log::info("✅ Reporte generado exitosamente: {$presentationUrl}");
            
            return [
                'success' => true,
                'presentation_id' => $presentationId,
                'presentation_url' => $presentationUrl,
                'slides_count' => count($reportData['slides']),
            ];
            
        } catch (Exception $e) {
            Log::error("❌ Error generando reporte: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepara todos los datos necesarios para el reporte usando la nueva jerarquía (versión optimizada)
     */
    public function prepareReportDataOptimized(Report $report): array
    {
        $slides = [];
        
        // Slide 1: Portada
        $slides[] = [
            'type' => 'title',
            'title' => $report->name,
            'subtitle' => 'Reporte Generado Automáticamente',
        ];
        
        // Slide 2: Información básica
        $slides[] = [
            'type' => 'content',
            'title' => 'Información del Reporte',
            'content' => [
                'Período' => $report->period_start . ' - ' . $report->period_end,
                'Estado' => 'Generado',
                'Fecha' => now()->format('Y-m-d H:i:s'),
            ],
        ];
        
        // Obtener datos reales de Facebook por Fan Page
        $facebookData = $this->getFacebookDataByFanPages($report);
        
        // Slide 3: Resumen general
        $slides[] = [
            'type' => 'content',
            'title' => 'Resumen General',
            'content' => [
                'Total de Fan Pages' => count($facebookData['fan_pages']),
                'Total de anuncios' => $facebookData['total_ads'],
                'Período analizado' => $report->period_start . ' - ' . $report->period_end,
            ],
        ];
        
        // Ordenar Fan Pages según la configuración del reporte
        $orderedFanPages = $facebookData['fan_pages'];
        if (!empty($report->fan_pages_order)) {
            $orderedFanPages = [];
            foreach ($report->fan_pages_order as $accountId) {
                foreach ($facebookData['fan_pages'] as $fanPage) {
                    if ($fanPage['page_id'] == $accountId) {
                        $orderedFanPages[] = $fanPage;
                        break;
                    }
                }
            }
        }
        
        // Generar slides por Fan Page en el orden especificado
        foreach ($orderedFanPages as $fanPage) {
            // Slide de título de Fan Page
            $slides[] = [
                'type' => 'brand_title',
                'title' => $fanPage['page_name'],
                'subtitle' => 'Fan Page - Resumen de Anuncios',
                'metrics' => [
                    'seguidores_facebook' => number_format($fanPage['followers_facebook']),
                    'seguidores_instagram' => number_format($fanPage['followers_instagram']),
                    'total_anuncios' => count($fanPage['ads']),
                    'alcance_total' => number_format($fanPage['total_reach']),
                    'impresiones_totales' => number_format($fanPage['total_impressions']),
                    'clicks_totales' => number_format($fanPage['total_clicks']),
                    'gasto_total' => '$' . number_format($fanPage['total_spend'], 2),
                ],
            ];
            
            // Slides de estadísticas por anuncio
            foreach ($fanPage['ads'] as $ad) {
                $slides[] = [
                    'type' => 'ad',
                    'title' => $ad['ad_name'],
                    'subtitle' => "Anuncio ID: {$ad['ad_id']}",
                    'metrics' => [
                        'alcance' => number_format($ad['reach']),
                        'impresiones' => number_format($ad['impressions']),
                        'frecuencia' => number_format($ad['frequency'], 2),
                        'clicks' => number_format($ad['clicks']),
                        'ctr' => number_format($ad['ctr'], 2) . '%',
                        'costo_por_resultado' => '$' . number_format($ad['cpc'], 2),
                        'importe_gastado' => '$' . number_format($ad['spend'], 2),
                        'resultados' => number_format($ad['clicks']),
                        'cpm' => '$' . number_format($ad['cpm'], 2),
                        'cpc' => '$' . number_format($ad['cpc'], 2),
                        'frecuencia_media' => number_format($ad['frequency'], 2),
                        'alcance_neto' => number_format($ad['reach']),
                    ],
                    'followers' => [
                        'facebook' => number_format($ad['followers']['facebook']),
                        'instagram' => number_format($ad['followers']['instagram']),
                    ],
                ];
            }
        }
        
        return [
            'slides' => $slides,
        ];
    }

    /**
     * Crea la diapositiva de portada
     */
    protected function createCoverSlide(Report $report, array $facebookData): array
    {
        return [
            'type' => 'cover',
            'title' => $facebookData['title'],
            'subtitle' => "Período: " . $facebookData['period']['start'] . " - " . $facebookData['period']['end'],
            'content' => [
                'description' => $facebookData['subtitle'],
                'total_brands' => $facebookData['statistics']['brands_count'] ?? 0,
                'total_campaigns' => $facebookData['statistics']['campaigns_count'] ?? 0,
            ],
            'layout' => 'title',
        ];
    }

    /**
     * Crea la diapositiva de resumen general
     */
    protected function createGeneralSummarySlide(Report $report, array $facebookData): array
    {
        $stats = $facebookData['statistics'];
        
        return [
            'type' => 'general_summary',
            'title' => 'Resumen General del Período',
            'subtitle' => 'Métricas Totales',
            'content' => [
                'total_reach' => number_format($stats['reach'] ?? 0),
                'total_impressions' => number_format($stats['impressions'] ?? 0),
                'total_clicks' => number_format($stats['clicks'] ?? 0),
                'total_interactions' => number_format($stats['total_interactions'] ?? 0),
                'total_brands' => $stats['brands_count'] ?? 0,
                'total_campaigns' => $stats['campaigns_count'] ?? 0,
                'average_ctr' => number_format($stats['ctr'] ?? 0, 2) . '%',
                'average_interaction_rate' => number_format($stats['interaction_rate'] ?? 0, 2) . '%',
            ],
            'layout' => 'content',
        ];
    }

    /**
     * Crea la diapositiva de título de marca
     */
    protected function createBrandTitleSlide(array $brandData): array
    {
        $stats = $brandData['statistics'];
        
        return [
            'type' => 'brand_title',
            'title' => $brandData['name'],
            'subtitle' => 'Resumen de Campañas',
            'content' => [
                'total_campaigns' => $stats['campaigns_count'] ?? 0,
                'total_reach' => number_format($stats['reach'] ?? 0),
                'total_impressions' => number_format($stats['impressions'] ?? 0),
                'total_clicks' => number_format($stats['clicks'] ?? 0),
                'total_interactions' => number_format($stats['total_interactions'] ?? 0),
                'average_ctr' => number_format($stats['ctr'] ?? 0, 2) . '%',
                'average_interaction_rate' => number_format($stats['interaction_rate'] ?? 0, 2) . '%',
            ],
            'layout' => 'content',
        ];
    }



    /**
     * Crea una diapositiva para una campaña específica
     */
    protected function createCampaignSlide(array $campaignData): array
    {
        $stats = $campaignData['statistics'];
        
        $slide = [
            'type' => 'campaign',
            'title' => $campaignData['name'],
            'subtitle' => "Campaña ID: {$campaignData['id']}",
            'content' => [
                'reach' => number_format($stats['reach'] ?? 0),
                'impressions' => number_format($stats['impressions'] ?? 0),
                'clicks' => number_format($stats['clicks'] ?? 0),
                'ctr' => number_format($stats['ctr'] ?? 0, 2) . '%',
                'cpm' => '$' . number_format($stats['cpm'] ?? 0, 2),
                'cpc' => '$' . number_format($stats['cpc'] ?? 0, 2),
                'frequency' => number_format($stats['frequency'] ?? 0, 2),
                'total_interactions' => number_format($stats['total_interactions'] ?? 0),
                'interaction_rate' => number_format($stats['interaction_rate'] ?? 0, 2) . '%',
                'video_views_p100' => number_format($stats['video_views']['p100'] ?? 0),
                'video_completion_rate' => number_format($stats['video_completion_rate'] ?? 0, 2) . '%',
            ],
            'layout' => 'image',
        ];
        
        // Agregar imagen si existe
        if (!empty($campaignData['image_url']) || !empty($campaignData['image_local_path'])) {
            $imageUrl = $campaignData['image_local_path'] ?? $campaignData['image_url'];
            $slide['image'] = [
                'url' => $imageUrl,
                'alt' => "Imagen de campaña: {$campaignData['name']}",
            ];
        }
        
        return $slide;
    }

    /**
     * Crea las diapositivas de gráficas
     */
    protected function createChartSlides(Report $report, array $facebookData): array
    {
        $slides = [];
        
        foreach ($report->charts_config as $chartConfig) {
            $chartData = $this->prepareChartData($facebookData, $chartConfig);
            
            $slides[] = [
                'type' => 'chart',
                'title' => $chartConfig['chart_title'] ?? 'Gráfica',
                'subtitle' => "Métrica: " . $this->getMetricLabel($chartConfig['metric']),
                'content' => [
                    'chart_type' => $chartConfig['chart_type'],
                    'chart_data' => $chartData,
                    'group_by' => $chartConfig['group_by'],
                    'include_totals' => $chartConfig['include_totals'] ?? true,
                ],
                'layout' => 'chart',
            ];
        }
        
        return $slides;
    }

    /**
     * Crea la diapositiva de resumen
     */
    protected function createSummarySlide(Report $report): array
    {
        return [
            'type' => 'summary',
            'title' => 'Resumen General',
            'subtitle' => 'Métricas Totales del Período',
            'content' => [
                'total_reach' => number_format($report->total_reach),
                'total_impressions' => number_format($report->total_impressions),
                'total_clicks' => number_format($report->total_clicks),
                'total_spend' => '$' . number_format($report->total_spend, 2),
                'average_ctr' => $report->total_impressions > 0 ? 
                    number_format(($report->total_clicks / $report->total_impressions) * 100, 2) . '%' : '0%',
                'average_cpm' => $report->total_impressions > 0 ? 
                    '$' . number_format(($report->total_spend / $report->total_impressions) * 1000, 2) : '$0',
                'average_cpc' => $report->total_clicks > 0 ? 
                    '$' . number_format($report->total_spend / $report->total_clicks, 2) : '$0',
                'generated_at' => now()->format('d/m/Y H:i:s'),
            ],
            'layout' => 'content',
        ];
    }

    /**
     * Prepara datos para las gráficas
     */
    protected function prepareChartData(array $facebookData, array $chartConfig): array
    {
        $metric = $chartConfig['metric'];
        $groupBy = $chartConfig['group_by'];
        
        $data = [];
        
        switch ($groupBy) {
            case 'brand':
                foreach ($facebookData['brands'] as $brandData) {
                    $value = $this->getBrandMetricValue($brandData, $metric);
                    $data[] = [
                        'label' => $brandData['name'],
                        'value' => $value,
                        'formatted_value' => $this->formatMetricValue($metric, $value),
                    ];
                }
                break;
                
            case 'campaign':
                foreach ($facebookData['brands'] as $brandData) {
                    foreach ($brandData['campaigns'] as $campaignData) {
                        $value = $this->getCampaignMetricValue($campaignData, $metric);
                        $data[] = [
                            'label' => $campaignData['name'],
                            'value' => $value,
                            'formatted_value' => $this->formatMetricValue($metric, $value),
                        ];
                    }
                }
                break;
                
            case 'date':
                // Implementar agrupación por fecha si es necesario
                break;
        }
        
        return $data;
    }

    /**
     * Obtiene el valor de una métrica para una marca
     */
    protected function getBrandMetricValue(array $brandData, string $metric): float
    {
        $stats = $brandData['statistics'];
        
        return match($metric) {
            'reach' => $stats['reach'] ?? 0,
            'impressions' => $stats['impressions'] ?? 0,
            'clicks' => $stats['clicks'] ?? 0,
            'ctr' => $stats['ctr'] ?? 0,
            'total_interactions' => $stats['total_interactions'] ?? 0,
            'interaction_rate' => $stats['interaction_rate'] ?? 0,
            'campaigns_count' => $stats['campaigns_count'] ?? 0,
            default => 0,
        };
    }

    /**
     * Obtiene el valor de una métrica para una campaña
     */
    protected function getCampaignMetricValue(array $campaignData, string $metric): float
    {
        $stats = $campaignData['statistics'];
        
        return match($metric) {
            'reach' => $stats['reach'] ?? 0,
            'impressions' => $stats['impressions'] ?? 0,
            'clicks' => $stats['clicks'] ?? 0,
            'ctr' => $stats['ctr'] ?? 0,
            'cpm' => $stats['cpm'] ?? 0,
            'cpc' => $stats['cpc'] ?? 0,
            'total_interactions' => $stats['total_interactions'] ?? 0,
            'interaction_rate' => $stats['interaction_rate'] ?? 0,
            'video_views_p100' => $stats['video_views']['p100'] ?? 0,
            'video_completion_rate' => $stats['video_completion_rate'] ?? 0,
            default => 0,
        };
    }

    /**
     * Formatea un valor de métrica según su tipo
     */
    protected function formatMetricValue(string $metric, float $value): string
    {
        return match($metric) {
            'spend', 'cpm', 'cpc' => '$' . number_format($value, 2),
            'ctr', 'interaction_rate', 'video_completion_rate' => number_format($value, 2) . '%',
            'reach', 'impressions', 'clicks', 'total_interactions', 'video_views_p100' => number_format($value),
            default => number_format($value, 2),
        };
    }

    /**
     * Obtiene la etiqueta de una métrica
     */
    protected function getMetricLabel(string $metric): string
    {
        return match($metric) {
            'reach' => 'Alcance',
            'impressions' => 'Impresiones',
            'clicks' => 'Clicks',
            'spend' => 'Gasto',
            'ctr' => 'CTR',
            'cpm' => 'CPM',
            'cpc' => 'CPC',
            'total_interactions' => 'Total de Interacciones',
            'interaction_rate' => 'Tasa de Interacción',
            'video_views_p100' => 'Vistas de Video al 100%',
            'video_completion_rate' => 'Tasa de Finalización de Video',
            default => ucfirst(str_replace('_', ' ', $metric)),
        };
    }

    /**
     * Crea una nueva presentación en Google Slides
     */
    protected function createPresentation(Report $report): string
    {
        $response = Http::timeout(120)->post($this->webAppUrl, [
            'action' => 'create_presentation',
            'title' => $report->name,
            'description' => $report->description ?? 'Reporte estadístico generado automáticamente',
        ]);
        
        if (!$response->successful()) {
            throw new Exception("Error creando presentación: " . $response->body());
        }
        
        $data = $response->json();
        
        // Verificar la estructura de la respuesta
        if (!isset($data['status']) || $data['status'] !== 'success') {
            throw new Exception("Error en respuesta del Google Apps Script: " . ($data['message'] ?? 'Error desconocido'));
        }
        
        if (!isset($data['data']['presentation_id'])) {
            throw new Exception("Respuesta del Google Apps Script no contiene presentation_id: " . json_encode($data));
        }
        
        return $data['data']['presentation_id'];
    }

    /**
     * Genera las diapositivas en la presentación (versión optimizada)
     */
    protected function generateSlidesOptimized(string $presentationId, array $reportData): void
    {
        // Enviar todas las diapositivas de una vez usando la nueva función
        $response = Http::timeout(300)->post($this->webAppUrl, [
            'action' => 'create_multiple_slides',
            'presentation_id' => $presentationId,
            'slides' => $reportData['slides'],
        ]);
        
        if (!$response->successful()) {
            Log::error("Error creando múltiples diapositivas: " . $response->body());
            throw new \Exception("Error creando diapositivas: " . $response->body());
        }
        
        $result = $response->json();
        
        // Verificar la estructura de la respuesta
        if (!isset($result['status']) || $result['status'] !== 'success') {
            throw new \Exception("Error en respuesta del Google Apps Script: " . ($result['message'] ?? 'Error desconocido'));
        }
        
        Log::info("Diapositivas creadas exitosamente: " . ($result['data']['slides_created'] ?? 0) . " slides");
    }

    /**
     * Genera las diapositivas en la presentación
     */
    protected function generateSlides(string $presentationId, array $reportData): void
    {
        foreach ($reportData['slides'] as $index => $slide) {
            $this->createSlide($presentationId, $slide, $index);
        }
    }

    /**
     * Crea una diapositiva individual
     */
    protected function createSlide(string $presentationId, array $slideData, int $index): void
    {
        $response = Http::timeout(120)->post($this->webAppUrl, [
            'action' => 'create_slide',
            'presentation_id' => $presentationId,
            'slide_index' => $index,
            'slide_data' => $slideData,
        ]);
        
        if (!$response->successful()) {
            Log::warning("Error creando diapositiva {$index}: " . $response->body());
        }
    }

    /**
     * Obtiene la URL de la presentación
     */
    protected function getPresentationUrl(string $presentationId): string
    {
        return "https://docs.google.com/presentation/d/{$presentationId}/edit";
    }

    /**
     * Actualiza el estado del reporte después de la generación
     */
    public function updateReportStatus(Report $report, array $result): void
    {
        if ($result['success']) {
            $report->markAsCompleted($result['presentation_url']);
        } else {
            $report->markAsFailed();
        }
    }

    /**
     * Obtiene datos de Facebook organizados por Fan Pages
     */
    public function getFacebookDataByFanPages(Report $report): array
    {
        $facebookAccounts = FacebookAccount::whereIn('id', $report->selected_facebook_accounts ?? [])->get();
        $fanPagesData = [];
        $totalAds = 0;

        foreach ($facebookAccounts as $account) {
            // Obtener la Fan Page seleccionada
            $pageId = $account->selected_page_id;
            if (!$pageId) {
                continue;
            }

            // Obtener anuncios específicos configurados para esta cuenta
            $adIds = $account->selected_ad_ids ?? [];
            
            if (empty($adIds)) {
                continue;
            }

            // Filtrar por anuncios específicos si están configurados en el reporte
            if (!empty($report->selected_ads)) {
                $adIds = array_intersect($adIds, $report->selected_ads);
            }

            if (empty($adIds)) {
                continue;
            }

            // Obtener datos de los anuncios
            $adsData = $this->getAdsDataForAccount($account, $adIds, $report->period_start, $report->period_end);
            
            if (!empty($adsData)) {
                // Calcular totales de la Fan Page
                $totalReach = array_sum(array_column($adsData, 'reach'));
                $totalImpressions = array_sum(array_column($adsData, 'impressions'));
                $totalClicks = array_sum(array_column($adsData, 'clicks'));
                $totalSpend = array_sum(array_column($adsData, 'spend'));

                // Obtener seguidores de la página
                $followers = $this->getPageFollowers($account);
                
                $fanPagesData[] = [
                    'page_id' => $pageId,
                    'page_name' => $account->account_name, // Usar el nombre de la cuenta como nombre de la página
                    'ads' => $adsData,
                    'total_reach' => $totalReach,
                    'total_impressions' => $totalImpressions,
                    'total_clicks' => $totalClicks,
                    'total_spend' => $totalSpend,
                    'followers_facebook' => $followers['facebook'],
                    'followers_instagram' => $followers['instagram'],
                ];

                $totalAds += count($adsData);
            }
        }

        return [
            'fan_pages' => $fanPagesData,
            'total_ads' => $totalAds,
        ];
    }

    /**
     * Obtiene datos de Facebook usando la nueva jerarquía
     */
    protected function getFacebookDataWithNewHierarchy(Report $report): array
    {
        $facebookAccounts = FacebookAccount::whereIn('id', $report->selected_facebook_accounts ?? [])->get();
        $allAdsData = [];
        $brandsData = [];

        foreach ($facebookAccounts as $account) {
            // Obtener anuncios específicos configurados
            $adIds = $account->selected_ad_ids ?? [];
            
            if (empty($adIds)) {
                continue;
            }

            // Filtrar por anuncios específicos si están configurados en el reporte
            if (!empty($report->selected_ads)) {
                $adIds = array_intersect($adIds, $report->selected_ads);
            }

            if (empty($adIds)) {
                continue;
            }

            // Obtener datos de los anuncios usando el método existente adaptado
            $adsData = $this->getAdsDataForAccount($account, $adIds, $report->period_start, $report->period_end);
            $allAdsData = array_merge($allAdsData, $adsData);
        }

        // Organizar por marcas
        foreach ($report->brands_config ?? [] as $brandConfig) {
            $brandName = $brandConfig['brand_name'] ?? 'Sin nombre';
            $brandAdIds = $brandConfig['ad_ids'] ?? [];
            
            $brandAds = [];
            foreach ($allAdsData as $adData) {
                if (in_array($adData['ad_id'], $brandAdIds)) {
                    $brandAds[] = $adData;
                }
            }
            
            if (!empty($brandAds)) {
                $brandsData[] = [
                    'brand_name' => $brandName,
                    'brand_identifier' => $brandConfig['brand_identifier'] ?? '',
                    'ads' => $brandAds,
                    'total_ads' => count($brandAds),
                ];
            }
        }

        return [
            'brands' => $brandsData,
            'total_brands' => count($brandsData),
            'total_ads' => count($allAdsData),
        ];
    }

    /**
     * Obtiene datos de anuncios para una cuenta específica
     */
    protected function getAdsDataForAccount(FacebookAccount $account, array $adIds, string $startDate, string $endDate): array
    {
        try {
            Log::info("🔍 Obteniendo datos para cuenta {$account->id}");
            Log::info("📅 Período: {$startDate} - {$endDate}");
            Log::info("🎯 Anuncios a buscar: " . implode(', ', $adIds));
            
            // Inicializar Facebook API
            \FacebookAds\Api::init(
                $account->app_id,
                $account->app_secret,
                $account->access_token
            );

            $adAccount = new \FacebookAds\Object\AdAccount('act_' . $account->selected_ad_account_id);
            
            $fields = [
                'ad_id',
                'ad_name',
                'campaign_id',
                'campaign_name',
                'impressions',
                'clicks',
                'spend',
                'reach',
                'frequency',
                'ctr',
                'cpm',
                'cpc',
                'actions',
                'video_p25_watched_actions',
                'video_p50_watched_actions',
                'video_p75_watched_actions',
                'video_p100_watched_actions',
                'conversions', // Cantidad de conversiones generadas
            ];

            $params = [
                'level' => 'ad',
                'time_range' => [
                    'since' => date('Y-m-d', strtotime($startDate)),
                    'until' => date('Y-m-d', strtotime($endDate)),
                ],
                'filtering' => [
                    [
                        'field' => 'ad.id',
                        'operator' => 'IN',
                        'value' => $adIds,
                    ],
                ],
            ];

            $insights = $adAccount->getInsights($fields, $params);
            $adsData = [];

            foreach ($insights as $insight) {
                // Procesar interacciones
                $actions = $insight->actions ?? [];
                $interactions = $this->processInteractions($actions);
                
                // Procesar videos vistos
                $videoViews = $this->processVideoViews($insight);
                
                $adsData[] = [
                    'ad_id' => $insight->ad_id,
                    'ad_name' => $insight->ad_name,
                    'campaign_id' => $insight->campaign_id,
                    'campaign_name' => $insight->campaign_name,
                    'impressions' => (int)($insight->impressions ?? 0),
                    'clicks' => (int)($insight->clicks ?? 0),
                    'spend' => (float)($insight->spend ?? 0),
                    'reach' => (int)($insight->reach ?? 0),
                    'frequency' => (float)($insight->frequency ?? 0),
                    'ctr' => (float)($insight->ctr ?? 0),
                    'cpm' => (float)($insight->cpm ?? 0),
                    'cpc' => (float)($insight->cpc ?? 0),
                    'interactions' => $interactions,
                    'total_interactions' => array_sum(array_column($interactions, 'value')),
                    'interaction_rate' => $this->calculateInteractionRate($insight->impressions ?? 0, $interactions),
                    'video_views' => $videoViews,
                    'video_completion_rate' => $this->calculateVideoCompletionRate($videoViews),
                    'conversions' => (int)($insight->conversions ?? 0), // Cantidad de conversiones generadas
                    'followers' => $this->getPageFollowers($account), // Seguidores de Facebook e Instagram
                    'region_data' => $this->getDefaultRegionData($insight), // Alcance según región (placeholder)
                ];
            }

            return $adsData;

        } catch (\Exception $e) {
            Log::error("Error obteniendo datos de anuncios para cuenta {$account->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Procesa las interacciones de un anuncio
     */
    protected function processInteractions($actions): array
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
    protected function processVideoViews($insight): array
    {
        return [
            'p25' => (int)($insight->video_p25_watched_actions ?? 0),
            'p50' => (int)($insight->video_p50_watched_actions ?? 0),
            'p75' => (int)($insight->video_p75_watched_actions ?? 0),
            'p100' => (int)($insight->video_p100_watched_actions ?? 0),
        ];
    }
    
    /**
     * Calcula la tasa de interacción
     */
    protected function calculateInteractionRate($impressions, $interactions): float
    {
        if ($impressions <= 0) return 0;
        
        $totalInteractions = array_sum(array_column($interactions, 'value'));
        return ($totalInteractions / $impressions) * 100;
    }
    
    /**
     * Calcula la tasa de finalización de video
     */
    protected function calculateVideoCompletionRate($videoViews): float
    {
        $p100 = $videoViews['p100'] ?? 0;
        $p25 = $videoViews['p25'] ?? 0;
        
        if ($p25 <= 0) return 0;
        
        return ($p100 / $p25) * 100;
    }
    
    /**
     * Obtiene la etiqueta de una interacción
     */
    protected function getInteractionLabel($actionType): string
    {
        return match($actionType) {
            'post_reaction' => 'Reacciones',
            'post_comment' => 'Comentarios',
            'post_share' => 'Compartidos',
            'post_save' => 'Guardados',
            'link_click' => 'Clicks en enlace',
            'video_view' => 'Vistas de video',
            'page_engagement' => 'Engagement de página',
            default => ucfirst(str_replace('_', ' ', $actionType)),
        };
    }

    /**
     * Obtiene el número de seguidores de Facebook e Instagram
     */
    protected function getPageFollowers(FacebookAccount $account): array
    {
        $followers = [
            'facebook' => 0,
            'instagram' => 0,
        ];

        try {
            // Usar Http client en lugar de file_get_contents para mejor manejo de errores
            $http = Http::timeout(30);
            
            // 1. Obtener información de la página de Facebook
            $fbResponse = $http->get("https://graph.facebook.com/v18.0/{$account->selected_page_id}", [
                'fields' => 'followers_count,fan_count',
                'access_token' => $account->access_token
            ]);
            
            if ($fbResponse->successful()) {
                $fbData = $fbResponse->json();
                // Usar followers_count o fan_count como fallback
                $followers['facebook'] = (int)($fbData['followers_count'] ?? $fbData['fan_count'] ?? 0);
            }

            // 2. Obtener Instagram Business Account ID
            $igResponse = $http->get("https://graph.facebook.com/v18.0/{$account->selected_page_id}", [
                'fields' => 'instagram_business_account',
                'access_token' => $account->access_token
            ]);
            
            if ($igResponse->successful()) {
                $igData = $igResponse->json();
                
                if (isset($igData['instagram_business_account']['id'])) {
                    $instagramAccountId = $igData['instagram_business_account']['id'];
                    
                    // 3. Obtener seguidores de Instagram
                    $igFollowersResponse = $http->get("https://graph.facebook.com/v18.0/{$instagramAccountId}", [
                        'fields' => 'followers_count',
                        'access_token' => $account->access_token
                    ]);
                    
                    if ($igFollowersResponse->successful()) {
                        $igFollowersData = $igFollowersResponse->json();
                        $followers['instagram'] = (int)($igFollowersData['followers_count'] ?? 0);
                    }
                }
            }
            
            Log::info("Seguidores obtenidos para cuenta {$account->id}: Facebook={$followers['facebook']}, Instagram={$followers['instagram']}");
            
        } catch (\Exception $e) {
            Log::warning("No se pudo obtener seguidores para la cuenta {$account->id}: " . $e->getMessage());
        }
        
        return $followers;
    }

    /**
     * Obtiene datos de región por defecto (placeholder)
     */
    protected function getDefaultRegionData($insight): array
    {
        return [
            'Sin datos de región' => [
                'reach' => $insight->reach ?? 0,
                'impressions' => $insight->impressions ?? 0,
            ]
        ];
    }

    /**
     * Crea una diapositiva para un anuncio específico
     */
    protected function createAdSlide(array $adData): array
    {
        return [
            'type' => 'ad',
            'title' => $adData['ad_name'],
            'subtitle' => "Anuncio ID: {$adData['ad_id']}",
            'content' => [
                'impressions' => number_format($adData['impressions']),
                'clicks' => number_format($adData['clicks']),
                'ctr' => number_format($adData['ctr'], 2) . '%',
                'spend' => '$' . number_format($adData['spend'], 2),
                'reach' => number_format($adData['reach']),
                'cpm' => '$' . number_format($adData['cpm'], 2),
                'cpc' => '$' . number_format($adData['cpc'], 2),
                'total_interactions' => number_format($adData['total_interactions']),
                'interaction_rate' => number_format($adData['interaction_rate'], 2) . '%',
                'video_views_p100' => number_format($adData['video_views']['p100'] ?? 0),
                'video_completion_rate' => number_format($adData['video_completion_rate'], 1) . '%',
            ],
            'image_url' => $adData['ad_image_url'] ?? null,
        ];
    }
}
