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
     * Genera un reporte completo en Google Slides
     */
    public function generateReport(Report $report): array
    {
        try {
            Log::info("🚀 Iniciando generación de reporte: {$report->name}");

            // 1. Preparar datos del reporte
            $reportData = $this->prepareReportData($report);
            
            // 2. Crear presentación en Google Slides
            $presentationId = $this->createPresentation($report);
            
            // 3. Generar diapositivas
            $this->generateSlides($presentationId, $reportData);
            
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
     * Prepara todos los datos necesarios para el reporte
     */
    public function prepareReportData(Report $report): array
    {
        // Obtener datos reales de Facebook
        $facebookData = $this->facebookDataService->getFacebookDataForReport($report);
        
        $slides = [];
        
        // Slide 1: Portada
        $slides[] = $this->createCoverSlide($report, $facebookData);
        
        // Slide 2: Resumen general
        $slides[] = $this->createGeneralSummarySlide($report, $facebookData);
        
        // Slides por marca
        foreach ($facebookData['brands'] as $brandData) {
            // Slide de título de marca
            $slides[] = $this->createBrandTitleSlide($brandData);
            
            // Slides de campañas de esta marca
            foreach ($brandData['campaigns'] as $campaignData) {
                $slides[] = $this->createCampaignSlide($campaignData);
            }
        }
        
        // Slides de gráficas estadísticas
        if (!empty($report->charts_config)) {
            $chartSlides = $this->createChartSlides($report, $facebookData);
            $slides = array_merge($slides, $chartSlides);
        }
        
        return [
            'slides' => $slides,
            'report' => $report,
            'facebook_data' => $facebookData,
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
        if (!isset($data['success']) || !$data['success']) {
            throw new Exception("Error en respuesta del Google Apps Script: " . ($data['error'] ?? 'Error desconocido'));
        }
        
        if (!isset($data['data']['presentation_id'])) {
            throw new Exception("Respuesta del Google Apps Script no contiene presentation_id: " . json_encode($data));
        }
        
        return $data['data']['presentation_id'];
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
}
