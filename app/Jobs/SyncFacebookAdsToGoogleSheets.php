<?php

namespace App\Jobs;

use App\Models\TaskLog;
use App\Services\GoogleSheetsService;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFacebookAdsToGoogleSheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    protected $task;

    public function __construct($task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $startTime = microtime(true);
        
        try {
            // Crear log de inicio
            $log = TaskLog::create([
                'automation_task_id' => $this->task->id,
                'started_at' => now(),
                'status' => 'running',
                'message' => 'Iniciando sincronización...',
                'records_processed' => 0,
                'execution_time' => 0,
            ]);

            // 1. Configurar Facebook API
            $fbAccount = $this->task->facebookAccount;
            Api::init(
                $fbAccount->app_id,
                $fbAccount->app_secret,
                $fbAccount->access_token
            );

            // Usar la cuenta publicitaria específica si está configurada
            $adAccountId = $fbAccount->selected_ad_account_id ?: $fbAccount->account_id;
            $account = new AdAccount('act_' . $adAccountId);
            
            Log::info("📊 Usando cuenta publicitaria: act_{$adAccountId}");
            
            // 2. Obtener datos de Facebook Ads (anuncios individuales)
            $ads = $this->getFacebookAdsData($account);
            
            // 3. Configurar Google Sheets Service
            $googleSheet = $this->task->googleSheet;
            $sheetsService = new GoogleSheetsService();
            
            // 4. Verificar si usar anuncios individuales o totales
            if ($googleSheet->individual_ads) {
                Log::info("📋 Usando anuncios individuales. Desplegando cada anuncio en una fila...");
                $result = $this->updateSheetWithIndividualAds($sheetsService, $googleSheet, $ads);
            } else {
                Log::info("📋 Usando totales por campaña...");
                // Calcular totales de todos los anuncios
                $totals = $this->calculateTotals($ads);
                $result = $sheetsService->updateSheet(
                    $googleSheet->spreadsheet_id,
                    $googleSheet->worksheet_name,
                    $totals,
                    $googleSheet->cell_mapping
                );
            }
            
            // 5. Actualizar log con resultado
            $executionTime = microtime(true) - $startTime;
            
            if ($result['success']) {
                // Actualizar log con éxito
                $log->update([
                    'status' => 'success',
                    'message' => $result['message'],
                    'completed_at' => now(),
                    'execution_time' => $executionTime,
                    'records_processed' => $result['updated_cells'] ?? count($ads),
                    'data_synced' => $ads,
                ]);
                
                // Generar reporte adicional
                $report = "=== REPORTE DE ANUNCIOS ===\n";
                $report .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n";
                $report .= "Total de anuncios: " . count($ads) . "\n\n";
                
                foreach ($ads as $index => $ad) {
                    $report .= "Anuncio " . ($index + 1) . ": {$ad['ad_name']}\n";
                    $report .= "  - Impresiones: " . number_format($ad['impressions']) . "\n";
                    $report .= "  - Clicks: " . number_format($ad['clicks']) . "\n";
                    $report .= "  - Gasto: $" . number_format($ad['spend'], 2) . "\n";
                    $report .= "  - CTR: " . number_format($ad['ctr'], 2) . "%\n";
                    $report .= "  - Interacciones: " . number_format($ad['total_interactions']) . "\n\n";
                }
                
                $report .= "=== FIN DEL REPORTE ===\n";
                Log::info("Reporte de anuncios generado:\n" . $report);
                
            } else {
                $log->update([
                    'status' => 'failed',
                    'message' => $result['message'],
                    'completed_at' => now(),
                    'execution_time' => $executionTime,
                    'error_message' => $result['message'],
                ]);
            }
            
            // 6. Actualizar última ejecución de la tarea
            $this->task->update([
                'last_run' => now(),
                'next_run' => $this->task->calculateNextRun(),
            ]);
            
            Log::info("Sincronización completada para tarea: {$this->task->name}", [
                'task_id' => $this->task->id,
                'records_processed' => count($ads),
                'execution_time' => $executionTime,
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            
            // Actualizar log con error
            if (isset($log)) {
                $log->update([
                    'status' => 'failed',
                    'message' => 'Error durante la sincronización',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                    'execution_time' => $executionTime,
                ]);
            }
            
            Log::error("Error en sincronización para tarea: {$this->task->name}", [
                'task_id' => $this->task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    private function getFacebookAdsData(AdAccount $account): array
    {
        $fields = [
            'ad_id',
            'ad_name',
            'adset_id',
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
            'actions', // Interacciones detalladas
            'action_values', // Valores de acciones
            'video_p25_watched_actions', // Videos vistos al 25%
            'video_p50_watched_actions', // Videos vistos al 50%
            'video_p75_watched_actions', // Videos vistos al 75%
            'video_p100_watched_actions', // Videos vistos al 100%
            'inline_link_clicks', // Clicks en enlaces
            'unique_clicks', // Clicks únicos
            'unique_inline_link_clicks', // Clicks únicos en enlaces
            'unique_actions', // Acciones únicas
            'cost_per_action_type', // Costo por tipo de acción
            'cost_per_unique_action_type', // Costo por acción única
        ];

        $params = [
            'level' => 'ad',
            'time_range' => [
                'since' => date('Y-m-d', strtotime('-7 days')),
                'until' => date('Y-m-d'),
            ],
        ];

        // Si hay una campaña específica configurada, filtrar por ella
        $fbAccount = $this->task->facebookAccount;
        if ($fbAccount->selected_campaign_id && $fbAccount->selected_campaign_id !== 'all') {
            $params['filtering'] = [
                [
                    'field' => 'campaign.id',
                    'operator' => 'IN',
                    'value' => [$fbAccount->selected_campaign_id],
                ],
            ];
            Log::info("🎯 Filtrando por campaña específica: {$fbAccount->selected_campaign_id}");
        }

        try {
            $insights = $account->getInsights($fields, $params);
            $ads = [];

            foreach ($insights as $insight) {
                // Procesar interacciones
                $actions = $insight->actions ?? [];
                $interactions = $this->processInteractions($actions);
                
                // Procesar videos vistos
                $videoViews = $this->processVideoViews($insight);
                
                // Obtener información de la imagen del anuncio
                $adImage = $this->getAdImage($insight->ad_id ?? null);
                
                $ads[] = [
                    'ad_id' => $insight->ad_id ?? null,
                    'ad_name' => $insight->ad_name ?? 'Sin nombre',
                    'campaign_name' => $insight->campaign_name ?? 'Sin campaña',
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
                    'ad_image_url' => $adImage['url'] ?? null,
                    'ad_image_local_path' => $adImage['local_path'] ?? null,
                ];
            }

            return $ads;

        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de anuncios de Facebook: ' . $e->getMessage());
            
            // Retornar array vacío en caso de error
            return [];
        }
    }

    /**
     * Procesa las interacciones de un anuncio
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
     * Obtiene la imagen del anuncio
     */
    private function getAdImage($adId): array
    {
        if (!$adId) {
            return ['url' => null, 'local_path' => null];
        }

        try {
            // Obtener información del anuncio
            $ad = new \FacebookAds\Object\Ad($adId);
            $adData = $ad->getSelf(['id', 'name', 'creative']);
            
            if (!isset($adData->creative) || !is_array($adData->creative) || !isset($adData->creative['id'])) {
                return ['url' => null, 'local_path' => null];
            }

            $creativeId = $adData->creative['id'];
            $creative = new \FacebookAds\Object\AdCreative($creativeId);
            $creativeData = $creative->getSelf(['id', 'name', 'image_url', 'image_hash', 'thumbnail_url', 'body', 'title']);
            
            $imageUrl = $creativeData->image_url ?? $creativeData->thumbnail_url ?? null;
            
            if (!$imageUrl) {
                return ['url' => null, 'local_path' => null];
            }

            // Descargar y guardar la imagen localmente
            $localPath = $this->downloadAndSaveImage($imageUrl, $adId);
            
            return [
                'url' => $imageUrl,
                'local_path' => $localPath,
            ];

        } catch (\Exception $e) {
            Log::warning("Error obteniendo imagen para anuncio {$adId}: " . $e->getMessage());
            return ['url' => null, 'local_path' => null];
        }
    }

    /**
     * Descarga y guarda una imagen localmente
     */
    private function downloadAndSaveImage(string $imageUrl, string $adId): ?string
    {
        try {
            $filename = 'ad_' . $adId . '_' . time() . '.jpg';
            $path = 'public/ad-images/' . $filename;
            
            // Crear directorio si no existe
            if (!\Illuminate\Support\Facades\Storage::exists('public/ad-images')) {
                \Illuminate\Support\Facades\Storage::makeDirectory('public/ad-images');
            }
            
            // Descargar imagen
            $imageContent = file_get_contents($imageUrl);
            if ($imageContent === false) {
                return null;
            }
            
            // Guardar imagen
            \Illuminate\Support\Facades\Storage::put($path, $imageContent);
            
            // Retornar ruta relativa para usar con asset()
            return 'storage/ad-images/' . $filename;
            
        } catch (\Exception $e) {
            Log::warning("Error descargando imagen para anuncio {$adId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza Google Sheets con datos de anuncios individuales usando mapeo de columnas
     */
    private function updateSheetWithIndividualAds($sheetsService, $googleSheet, array $ads): array
    {
        try {
            $cellMapping = $googleSheet->cell_mapping ?? [];
            $startRow = $googleSheet->start_row ?? 2;
            
            if (empty($cellMapping)) {
                return [
                    'success' => false,
                    'message' => 'No hay mapeo de columnas configurado',
                    'updated_cells' => 0,
                    'total_cells' => 0
                ];
            }

            $allUpdates = [];

            // Agregar headers en la primera fila
            $headers = [];
            foreach ($cellMapping as $metric => $column) {
                $headers["{$column}1"] = ucfirst(str_replace('_', ' ', $metric));
            }
            $allUpdates[] = $headers;

            // Procesar cada anuncio
            foreach ($ads as $index => $ad) {
                $row = $startRow + $index;
                $rowData = [];
                
                foreach ($cellMapping as $metric => $column) {
                    $value = $this->getAdValue($ad, $metric);
                    $rowData["{$column}{$row}"] = $value;
                }
                
                $allUpdates[] = $rowData;
            }

            // Combinar todas las actualizaciones
            $combinedUpdates = [];
            foreach ($allUpdates as $update) {
                $combinedUpdates = array_merge($combinedUpdates, $update);
            }

            // Limitar la cantidad de celdas para evitar errores del Google Apps Script
            if (count($combinedUpdates) > 200) {
                Log::warning("⚠️ Demasiadas celdas (" . count($combinedUpdates) . "). Limitando a 200 celdas.");
                $combinedUpdates = array_slice($combinedUpdates, 0, 200, true);
            }

            Log::info("📊 Total de celdas a actualizar: " . count($combinedUpdates));

            // Usar el servicio de Google Sheets para actualizar
            return $sheetsService->updateSheet(
                $googleSheet->spreadsheet_id,
                $googleSheet->worksheet_name,
                $combinedUpdates,
                null // No usar cell_mapping ya que estamos enviando las celdas directamente
            );

        } catch (\Exception $e) {
            Log::error('Error actualizando Google Sheets con anuncios individuales: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'updated_cells' => 0,
                'total_cells' => 0
            ];
        }
    }

    /**
     * Calcula totales de todos los anuncios
     */
    private function calculateTotals(array $ads): array
    {
        if (empty($ads)) {
            return [
                'impressions' => 0,
                'clicks' => 0,
                'spend' => 0,
                'reach' => 0,
                'ctr' => 0,
                'cpm' => 0,
                'cpc' => 0,
                'total_interactions' => 0,
                'interaction_rate' => 0,
                'video_views_p100' => 0,
            ];
        }

        $totalImpressions = array_sum(array_column($ads, 'impressions'));
        $totalClicks = array_sum(array_column($ads, 'clicks'));
        $totalSpend = array_sum(array_column($ads, 'spend'));
        $totalReach = array_sum(array_column($ads, 'reach'));
        $totalInteractions = array_sum(array_column($ads, 'total_interactions'));
        $totalVideoViews = array_sum(array_column($ads, 'video_views.p100'));

        return [
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'spend' => $totalSpend,
            'reach' => $totalReach,
            'ctr' => $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0,
            'cpm' => $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0,
            'cpc' => $totalClicks > 0 ? $totalSpend / $totalClicks : 0,
            'total_interactions' => $totalInteractions,
            'interaction_rate' => $totalImpressions > 0 ? ($totalInteractions / $totalImpressions) * 100 : 0,
            'video_views_p100' => $totalVideoViews,
        ];
    }

    /**
     * Obtiene el valor formateado de un anuncio para una métrica específica
     */
    private function getAdValue(array $ad, string $metric): string
    {
        return match($metric) {
            'ad_name' => $ad['ad_name'] ?? 'Sin nombre',
            'ad_id' => $ad['ad_id'] ?? 'N/A',
            'campaign_name' => $ad['campaign_name'] ?? 'Sin campaña',
            'impressions' => number_format($ad['impressions'] ?? 0),
            'clicks' => number_format($ad['clicks'] ?? 0),
            'spend' => '$' . number_format($ad['spend'] ?? 0, 2),
            'reach' => number_format($ad['reach'] ?? 0),
            'ctr' => number_format($ad['ctr'] ?? 0, 2) . '%',
            'cpm' => '$' . number_format($ad['cpm'] ?? 0, 2),
            'cpc' => '$' . number_format($ad['cpc'] ?? 0, 2),
            'total_interactions' => number_format($ad['total_interactions'] ?? 0),
            'interaction_rate' => number_format($ad['interaction_rate'] ?? 0, 2) . '%',
            'video_views_p100' => number_format($ad['video_views']['p100'] ?? 0),
            'ad_image_url' => $this->formatImageUrl($ad['ad_image_url'] ?? 'Sin imagen'),
            'ad_image_local_path' => $this->formatImageUrl($ad['ad_image_local_path'] ?? 'Sin imagen local'),
            default => 'N/A',
        };
    }

    /**
     * Formatea la URL de la imagen para evitar problemas con Google Apps Script
     */
    private function formatImageUrl(?string $url): string
    {
        if (!$url || $url === 'Sin imagen' || $url === 'Sin imagen local') {
            return 'Sin imagen';
        }

        // Si es una URL muy larga, acortarla
        if (strlen($url) > 200) {
            return 'URL muy larga - Ver en Facebook';
        }

        // Si es una URL local, convertirla a URL completa
        if (strpos($url, 'storage/') === 0) {
            return config('app.url') . '/' . $url;
        }

        return $url;
    }
}
