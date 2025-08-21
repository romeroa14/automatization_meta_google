<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportBrand;
use App\Models\ReportCampaign;
use App\Models\FacebookAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleSlidesReportService
{
    protected string $webAppUrl;
    protected array $defaultSlideLayouts;

    public function __construct()
    {
        $this->webAppUrl = env('GOOGLE_WEBAPP_URL_slides');
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
    protected function prepareReportData(Report $report): array
    {
        $slides = [];
        
        // Slide 1: Portada
        $slides[] = $this->createCoverSlide($report);
        
        // Slides por marca
        $brands = $report->brands()->ordered()->get();
        foreach ($brands as $brand) {
            $brandSlides = $this->createBrandSlides($report, $brand);
            $slides = array_merge($slides, $brandSlides);
        }
        
        // Slides de gráficas
        if (!empty($report->charts_config)) {
            $chartSlides = $this->createChartSlides($report);
            $slides = array_merge($slides, $chartSlides);
        }
        
        // Slide de resumen
        $slides[] = $this->createSummarySlide($report);
        
        return [
            'slides' => $slides,
            'report' => $report,
            'brands' => $brands,
        ];
    }

    /**
     * Crea la diapositiva de portada
     */
    protected function createCoverSlide(Report $report): array
    {
        return [
            'type' => 'cover',
            'title' => $report->name,
            'subtitle' => "Período: " . $report->period_start->format('d/m/Y') . " - " . $report->period_end->format('d/m/Y'),
            'content' => [
                'description' => $report->description,
                'total_days' => $report->period_days,
                'total_brands' => $report->total_brands,
                'total_campaigns' => $report->total_campaigns,
            ],
            'layout' => 'title',
        ];
    }

    /**
     * Crea las diapositivas para una marca específica
     */
    protected function createBrandSlides(Report $report, ReportBrand $brand): array
    {
        $slides = [];
        
        // Slide de título de marca
        $slides[] = [
            'type' => 'brand_title',
            'title' => $brand->brand_name,
            'subtitle' => "Resumen de Campañas",
            'content' => [
                'total_campaigns' => $brand->total_campaigns,
                'total_reach' => number_format($brand->total_reach),
                'total_impressions' => number_format($brand->total_impressions),
                'total_clicks' => number_format($brand->total_clicks),
                'total_spend' => '$' . number_format($brand->total_spend, 2),
                'average_ctr' => number_format($brand->average_ctr, 2) . '%',
                'average_cpm' => '$' . number_format($brand->average_cpm, 2),
                'average_cpc' => '$' . number_format($brand->average_cpc, 2),
            ],
            'layout' => 'content',
        ];
        
        // Slides de campañas individuales
        $campaigns = $brand->campaigns()->ordered()->get();
        foreach ($campaigns as $campaign) {
            $slides[] = $this->createCampaignSlide($campaign);
        }
        
        return $slides;
    }

    /**
     * Crea una diapositiva para una campaña específica
     */
    protected function createCampaignSlide(ReportCampaign $campaign): array
    {
        $slide = [
            'type' => 'campaign',
            'title' => $campaign->campaign_name,
            'subtitle' => "Campaña ID: {$campaign->campaign_id}",
            'content' => [
                'reach' => number_format($campaign->reach),
                'impressions' => number_format($campaign->impressions),
                'clicks' => number_format($campaign->clicks),
                'spend' => $campaign->formatted_spend,
                'ctr' => $campaign->formatted_ctr,
                'cpm' => $campaign->formatted_cpm,
                'cpc' => $campaign->formatted_cpc,
                'frequency' => $campaign->formatted_frequency,
                'total_interactions' => number_format($campaign->total_interactions),
                'interaction_rate' => $campaign->formatted_interaction_rate,
                'video_views' => number_format($campaign->video_views),
                'video_completion_rate' => $campaign->formatted_video_completion_rate,
            ],
            'layout' => 'image',
        ];
        
        // Agregar imagen si existe
        if ($campaign->hasLocalImage()) {
            $slide['image'] = [
                'url' => $campaign->ad_image_path,
                'alt' => "Imagen de campaña: {$campaign->campaign_name}",
            ];
        }
        
        return $slide;
    }

    /**
     * Crea las diapositivas de gráficas
     */
    protected function createChartSlides(Report $report): array
    {
        $slides = [];
        
        foreach ($report->charts_config as $chartConfig) {
            $chartData = $this->prepareChartData($report, $chartConfig);
            
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
    protected function prepareChartData(Report $report, array $chartConfig): array
    {
        $metric = $chartConfig['metric'];
        $groupBy = $chartConfig['group_by'];
        
        $data = [];
        
        switch ($groupBy) {
            case 'brand':
                $brands = $report->brands()->ordered()->get();
                foreach ($brands as $brand) {
                    $value = $this->getBrandMetricValue($brand, $metric);
                    $data[] = [
                        'label' => $brand->brand_name,
                        'value' => $value,
                        'formatted_value' => $this->formatMetricValue($metric, $value),
                    ];
                }
                break;
                
            case 'campaign':
                $campaigns = $report->campaigns()->ordered()->get();
                foreach ($campaigns as $campaign) {
                    $value = $this->getCampaignMetricValue($campaign, $metric);
                    $data[] = [
                        'label' => $campaign->campaign_name,
                        'value' => $value,
                        'formatted_value' => $this->formatMetricValue($metric, $value),
                    ];
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
    protected function getBrandMetricValue(ReportBrand $brand, string $metric): float
    {
        return match($metric) {
            'reach' => $brand->total_reach,
            'impressions' => $brand->total_impressions,
            'clicks' => $brand->total_clicks,
            'spend' => $brand->total_spend,
            'ctr' => $brand->average_ctr,
            'cpm' => $brand->average_cpm,
            'cpc' => $brand->average_cpc,
            'total_interactions' => $brand->campaigns()->get()->sum(function($campaign) {
                return $campaign->statistics['total_interactions'] ?? 0;
            }),
            'interaction_rate' => $brand->campaigns()->get()->avg(function($campaign) {
                return $campaign->statistics['interaction_rate'] ?? 0;
            }),
            'video_views_p100' => $brand->campaigns()->get()->sum(function($campaign) {
                return $campaign->statistics['video_views_p100'] ?? 0;
            }),
            'video_completion_rate' => $brand->campaigns()->get()->avg(function($campaign) {
                return $campaign->statistics['video_completion_rate'] ?? 0;
            }),
            default => 0,
        };
    }

    /**
     * Obtiene el valor de una métrica para una campaña
     */
    protected function getCampaignMetricValue(ReportCampaign $campaign, string $metric): float
    {
        return match($metric) {
            'reach' => $campaign->reach,
            'impressions' => $campaign->impressions,
            'clicks' => $campaign->clicks,
            'spend' => $campaign->spend,
            'ctr' => $campaign->ctr,
            'cpm' => $campaign->cpm,
            'cpc' => $campaign->cpc,
            'total_interactions' => $campaign->total_interactions,
            'interaction_rate' => $campaign->interaction_rate,
            'video_views_p100' => $campaign->video_views,
            'video_completion_rate' => $campaign->video_completion_rate,
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
