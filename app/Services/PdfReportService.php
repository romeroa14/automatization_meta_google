<?php

namespace App\Services;

use App\Models\Report;
use App\Models\FacebookAccount;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfReportService
{
    /**
     * Genera un reporte en PDF
     */
    public function generateReport(Report $report): array
    {
        try {
            Log::info("🚀 Iniciando generación de reporte PDF: {$report->name}");
            
            // Obtener datos de Facebook
            $facebookData = $this->getFacebookDataByFanPages($report);
            
            // Preparar datos para el template
            $reportData = [
                'report' => $report,
                'facebook_data' => $facebookData,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'period' => [
                    'start' => $report->period_start->format('d/m/Y'),
                    'end' => $report->period_end->format('d/m/Y'),
                ],
            ];
            
            // Generar PDF
            $pdf = Pdf::loadView('reports.pdf.report', $reportData);
            
            // Configurar el PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'Arial',
                'chroot' => public_path(),
            ]);
            
            // Generar nombre del archivo
            $filename = 'reporte_' . strtolower(str_replace(' ', '_', $report->name)) . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            $filepath = storage_path('app/public/reports/' . $filename);
            
            // Asegurar que el directorio existe
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            // Guardar el PDF
            $pdf->save($filepath);
            
            // URL pública del archivo
            $publicUrl = Storage::url('reports/' . $filename);
            
            Log::info("✅ Reporte PDF generado exitosamente: {$filepath}");
            
            return [
                'success' => true,
                'file_path' => $filepath,
                'file_url' => $publicUrl,
                'filename' => $filename,
                'slides_count' => count($facebookData['fan_pages']) + 3, // Título + Resumen + Fan Pages
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Error generando reporte PDF: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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
        $totalReach = 0;
        $totalImpressions = 0;
        $totalClicks = 0;
        $totalSpend = 0;

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
                $pageReach = array_sum(array_column($adsData, 'reach'));
                $pageImpressions = array_sum(array_column($adsData, 'impressions'));
                $pageClicks = array_sum(array_column($adsData, 'clicks'));
                $pageSpend = array_sum(array_column($adsData, 'spend'));
                
                // Obtener seguidores y nombre de la página
                $followers = $this->getPageFollowers($account);
                $pageName = $this->getPageName($account);
                
                $fanPagesData[] = [
                    'account_id' => $account->id,
                    'page_id' => $pageId,
                    'page_name' => $pageName,
                    'ads' => $adsData,
                    'total_ads' => count($adsData),
                    'total_reach' => $pageReach,
                    'total_impressions' => $pageImpressions,
                    'total_clicks' => $pageClicks,
                    'total_spend' => $pageSpend,
                    'followers_facebook' => $followers['facebook'],
                    'followers_instagram' => $followers['instagram'],
                ];
                
                $totalAds += count($adsData);
                $totalReach += $pageReach;
                $totalImpressions += $pageImpressions;
                $totalClicks += $pageClicks;
                $totalSpend += $pageSpend;
            }
        }

        // Ordenar Fan Pages según la configuración del reporte
        if (!empty($report->fan_pages_order)) {
            $orderedFanPages = [];
            foreach ($report->fan_pages_order as $accountId) {
                foreach ($fanPagesData as $fanPage) {
                    if ($fanPage['account_id'] == $accountId) {
                        $orderedFanPages[] = $fanPage;
                        break;
                    }
                }
            }
            $fanPagesData = $orderedFanPages;
        }

        return [
            'fan_pages' => $fanPagesData,
            'total_ads' => $totalAds,
            'total_reach' => $totalReach,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_spend' => $totalSpend,
        ];
    }

    /**
     * Obtiene datos de anuncios para una cuenta específica
     */
    protected function getAdsDataForAccount(FacebookAccount $account, array $adIds, $startDate, $endDate): array
    {
        try {
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
                'conversions',
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
                    'conversions' => (int)($insight->conversions ?? 0),
                    'followers' => $this->getPageFollowers($account),
                    'region_data' => $this->getDefaultRegionData($insight),
                ];
            }

            // Obtener imágenes de los anuncios
            $adsData = $this->addAdImages($adsData, $account);
            
            return $adsData;

        } catch (\Exception $e) {
            Log::error("Error obteniendo datos de anuncios para cuenta {$account->id}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Agrega las imágenes de los anuncios a los datos
     */
    protected function addAdImages(array $adsData, FacebookAccount $account): array
    {
        try {
            \FacebookAds\Api::init(
                $account->app_id,
                $account->app_secret,
                $account->access_token
            );

            foreach ($adsData as &$adData) {
                $adId = $adData['ad_id'];
                
                // Obtener el anuncio completo para acceder a los creativos
                $ad = new \FacebookAds\Object\Ad($adId);
                $ad->read(['creative']);
                
                if ($ad->creative) {
                    $creative = new \FacebookAds\Object\AdCreative($ad->creative['id']);
                    $creative->read(['image_url', 'image_hash', 'thumbnail_url']);
                    
                    // Usar thumbnail_url si está disponible, sino image_url
                    $imageUrl = $creative->thumbnail_url ?? $creative->image_url ?? null;
                    
                    if ($imageUrl) {
                        $adData['ad_image_url'] = $imageUrl;
                        Log::info("Imagen obtenida para anuncio {$adId}: {$imageUrl}");
                    } else {
                        Log::warning("No se encontró imagen para anuncio {$adId}");
                    }
                } else {
                    Log::warning("No se encontró creative para anuncio {$adId}");
                }
            }

        } catch (\Exception $e) {
            Log::warning("Error obteniendo imágenes de anuncios: " . $e->getMessage());
        }

        return $adsData;
    }

    /**
     * Obtiene el nombre real de la página
     */
    protected function getPageName(FacebookAccount $account): string
    {
        try {
            $http = \Illuminate\Support\Facades\Http::timeout(30);
            
            // Obtener información básica de la página
            $response = $http->get("https://graph.facebook.com/v18.0/{$account->selected_page_id}", [
                'fields' => 'name',
                'access_token' => $account->access_token
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $pageName = $data['name'] ?? null;
                
                if ($pageName) {
                    Log::info("Nombre de página obtenido para cuenta {$account->id}: {$pageName}");
                    return $pageName;
                }
            }
            
            Log::warning("No se pudo obtener el nombre de la página para cuenta {$account->id}");
            
        } catch (\Exception $e) {
            Log::warning("Error obteniendo nombre de página para cuenta {$account->id}: " . $e->getMessage());
        }
        
        // Fallback al nombre de la cuenta o un nombre genérico
        return $account->account_name ?? 'Fan Page';
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
            $http = \Illuminate\Support\Facades\Http::timeout(30);
            
            // 1. Obtener información de la página de Facebook
            $fbResponse = $http->get("https://graph.facebook.com/v18.0/{$account->selected_page_id}", [
                'fields' => 'followers_count,fan_count',
                'access_token' => $account->access_token
            ]);
            
            if ($fbResponse->successful()) {
                $fbData = $fbResponse->json();
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
                        'label' => $this->getInteractionLabel($action['action_type']),
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
}
