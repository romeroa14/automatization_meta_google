<?php

namespace App\Services;

use App\Models\FacebookAccount;
use App\Models\GoogleSheet;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutoSheetGeneratorService
{
    /**
     * Genera una hoja automática con todas las estadísticas de Facebook Ads
     */
    public function generateAutoSheet(GoogleSheet $googleSheet): array
    {
        try {
            // Obtener la cuenta de Facebook
            $facebookAccount = FacebookAccount::find($googleSheet->facebook_account_id);
            if (!$facebookAccount) {
                throw new \Exception('Cuenta de Facebook no encontrada');
            }

            // Inicializar la API de Facebook
            Api::init(
                config('services.facebook.app_id'),
                config('services.facebook.app_secret'),
                $facebookAccount->access_token
            );

            $account = new AdAccount('act_' . $facebookAccount->account_id);

            // Obtener datos de campañas y anuncios
            $campaignData = $this->getCampaignData($account, $googleSheet->campaign_id);
            
            // Generar la estructura de la hoja
            $sheetData = $this->generateSheetStructure($campaignData);
            
            // Crear la hoja en Google Sheets
            $result = $this->createSheetInGoogle($googleSheet, $sheetData);

            return [
                'success' => true,
                'message' => 'Hoja automática creada exitosamente',
                'data' => $result
            ];

        } catch (\Exception $e) {
            Log::error('Error generando hoja automática: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene datos de campañas y anuncios
     */
    private function getCampaignData($account, $campaignId = null): array
    {
        $data = [];

        try {
            // Obtener campañas
            $campaigns = $account->getCampaigns(['id', 'name', 'status']);
            
            foreach ($campaigns as $campaign) {
                if ($campaignId && $campaignId !== 'all' && $campaign->id !== $campaignId) {
                    continue;
                }

                // Obtener anuncios de la campaña
                $ads = $this->getAdsWithStats($account, $campaign->id);
                
                $data[] = [
                    'campaign' => [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'status' => $campaign->status,
                    ],
                    'ads' => $ads
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de campañas: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Obtiene anuncios con estadísticas completas
     */
    private function getAdsWithStats($account, $campaignId): array
    {
        try {
            $fields = [
                'ad_id',
                'ad_name',
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
                'time_range' => ['since' => now()->subDays(30)->format('Y-m-d'), 'until' => now()->format('Y-m-d')],
                'filtering' => [
                    [
                        'field' => 'campaign.id',
                        'operator' => 'IN',
                        'value' => [$campaignId],
                    ],
                ],
            ];

            $insights = $account->getInsights($fields, $params);
            
            $ads = [];
            foreach ($insights as $insight) {
                // Procesar interacciones
                $actions = $insight->actions ?? [];
                $interactions = $this->processInteractions($actions);
                
                // Procesar videos vistos
                $videoViews = $this->processVideoViews($insight);
                
                // Obtener información creativa
                $creative = $this->getAdCreative($insight->ad_id);
                
                $ads[] = [
                    'ad_id' => $insight->ad_id ?? null,
                    'ad_name' => $insight->ad_name ?? 'Sin nombre',
                    'creative' => $creative,
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
                    'interactions' => $interactions,
                    'total_interactions' => array_sum(array_column($interactions, 'value')),
                    'interaction_rate' => $this->calculateInteractionRate($insight->impressions ?? 0, $interactions),
                    'video_views' => $videoViews,
                    'video_completion_rate' => $this->calculateVideoCompletionRate($videoViews),
                ];
            }

            return $ads;

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de anuncios: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene información creativa del anuncio
     */
    private function getAdCreative($adId): ?array
    {
        try {
            $ad = new \FacebookAds\Object\Ad($adId);
            $adData = $ad->getSelf(['id', 'name', 'creative']);
            
            if (isset($adData->creative) && is_array($adData->creative) && isset($adData->creative['id'])) {
                $creativeId = $adData->creative['id'];
                $creative = new \FacebookAds\Object\AdCreative($creativeId);
                $creativeData = $creative->getSelf(['id', 'name', 'image_url', 'image_hash', 'thumbnail_url', 'body', 'title']);
                
                return [
                    'id' => $creativeData->id ?? null,
                    'name' => $creativeData->name ?? null,
                    'image_url' => $creativeData->image_url ?? null,
                    'image_hash' => $creativeData->image_hash ?? null,
                    'thumbnail_url' => $creativeData->thumbnail_url ?? null,
                    'body' => $creativeData->body ?? null,
                    'title' => $creativeData->title ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("Error obteniendo creative para anuncio {$adId}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Procesa las interacciones
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
            'p25' => (int)($insight->video_p25_watched_actions ?? 0),
            'p50' => (int)($insight->video_p50_watched_actions ?? 0),
            'p75' => (int)($insight->video_p75_watched_actions ?? 0),
            'p100' => (int)($insight->video_p100_watched_actions ?? 0),
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
     * Obtiene etiquetas legibles para tipos de interacción
     */
    private function getInteractionLabel($actionType): string
    {
        $labels = [
            'like' => 'Me gusta',
            'comment' => 'Comentarios',
            'share' => 'Compartir',
            'post_reaction' => 'Reacciones',
            'post_engagement' => 'Engagement',
            'link_click' => 'Clicks en enlace',
            'video_view' => 'Vistas de video',
            'page_engagement' => 'Engagement de página',
            'onsite_conversion.messaging_first_reply' => 'Primera respuesta',
            'onsite_conversion.messaging_conversation_started_7d' => 'Conversación iniciada',
        ];
        
        return $labels[$actionType] ?? ucfirst(str_replace('_', ' ', $actionType));
    }

    /**
     * Genera la estructura de datos para la hoja
     */
    private function generateSheetStructure(array $campaignData): array
    {
        $sheetData = [];
        
        // Encabezados principales
        $sheetData[] = [
            'A1' => '📊 REPORTE DE ANUNCIOS FACEBOOK',
            'B1' => 'Generado: ' . now()->format('d/m/Y H:i'),
        ];

        $row = 3;
        
        foreach ($campaignData as $campaign) {
            // Encabezado de campaña
            $sheetData[] = [
                "A{$row}" => "🎯 CAMPAÑA: {$campaign['campaign']['name']}",
                "B{$row}" => "ID: {$campaign['campaign']['id']}",
                "C{$row}" => "Estado: {$campaign['campaign']['status']}",
            ];
            $row++;

            // Encabezados de columnas
            $sheetData[] = [
                "A{$row}" => '📸 Anuncio',
                "B{$row}" => 'ID',
                "C{$row}" => 'Impresiones',
                "D{$row}" => 'Alcance',
                "E{$row}" => 'Clicks',
                "F{$row}" => 'Gasto ($)',
                "G{$row}" => 'CTR (%)',
                "H{$row}" => 'Interacciones',
                "I{$row}" => 'Tasa Interacción (%)',
                "J{$row}" => 'Videos Completados',
                "K{$row}" => 'CPM ($)',
                "L{$row}" => 'CPC ($)',
                "M{$row}" => 'Título del Creative',
            ];
            $row++;

            // Datos de anuncios
            foreach ($campaign['ads'] as $ad) {
                $sheetData[] = [
                    "A{$row}" => $ad['ad_name'],
                    "B{$row}" => $ad['ad_id'],
                    "C{$row}" => number_format($ad['impressions']),
                    "D{$row}" => number_format($ad['reach']),
                    "E{$row}" => number_format($ad['clicks']),
                    "F{$row}" => '$' . number_format($ad['spend'], 2),
                    "G{$row}" => number_format($ad['ctr'], 2) . '%',
                    "H{$row}" => number_format($ad['total_interactions']),
                    "I{$row}" => number_format($ad['interaction_rate'], 2) . '%',
                    "J{$row}" => $ad['video_views']['p100'] > 0 ? number_format($ad['video_views']['p100']) : '-',
                    "K{$row}" => '$' . number_format($ad['cpm'], 2),
                    "L{$row}" => '$' . number_format($ad['cpc'], 2),
                    "M{$row}" => $ad['creative']['title'] ?? 'Sin título',
                ];
                $row++;
            }

            // Espacio entre campañas
            $row += 2;
        }

        return $sheetData;
    }

    /**
     * Crea la hoja en Google Sheets
     */
    private function createSheetInGoogle(GoogleSheet $googleSheet, array $sheetData): array
    {
        try {
            $webappUrl = config('services.google.webapp_url') ?? env('GOOGLE_WEBAPP_URL');
            
            if (empty($webappUrl)) {
                throw new \Exception('URL del Web App no configurada');
            }

            // Crear la nueva hoja
            $response = Http::timeout(60)
                ->withOptions(['allow_redirects' => true])
                ->get($webappUrl, [
                    'action' => 'create_sheet',
                    'spreadsheet_id' => $googleSheet->spreadsheet_id,
                    'sheet_name' => $googleSheet->auto_sheet_name ?? 'Estadísticas Completas',
                    'data' => json_encode($sheetData),
                    'timestamp' => now()->toISOString()
                ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['success']) && $result['success']) {
                    return $result;
                } else {
                    throw new \Exception('Error en la respuesta del Web App: ' . ($result['message'] ?? 'Error desconocido'));
                }
            } else {
                throw new \Exception('Error de comunicación con el Web App: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Error creando hoja en Google: ' . $e->getMessage());
            throw $e;
        }
    }
}
