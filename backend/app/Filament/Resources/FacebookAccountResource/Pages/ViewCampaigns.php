<?php

namespace App\Filament\Resources\FacebookAccountResource\Pages;

use App\Filament\Resources\FacebookAccountResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount;

class ViewCampaigns extends Page
{
    use InteractsWithForms;

    protected static string $resource = FacebookAccountResource::class;

    protected static string $view = 'filament.resources.facebook-account-resource.pages.view-campaigns';

    public $record;
    public $selectedCampaignIds = [];
    public $selectedAdIds = [];
    public $campaigns = [];
    public $ads = [];
    public $dateRange = 'last_7d';
    public $isLoading = false;

    public function mount($record): void
    {
        // Si record es un string (ID), cargar el modelo
        if (is_string($record)) {
            $this->record = FacebookAccount::findOrFail($record);
        } else {
            $this->record = $record;
        }
        
        // Cargar las campañas y anuncios ya seleccionados en la configuración
        $this->selectedCampaignIds = $this->record->selected_campaign_ids ?? [];
        $this->selectedAdIds = $this->record->selected_ad_ids ?? [];
        
        $this->loadConfiguredData();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración Actual')
                    ->description('Campañas y anuncios configurados para esta cuenta')
                    ->schema([
                        Select::make('selectedCampaignIds')
                            ->label('Campañas Configuradas')
                            ->options($this->getCampaignOptions())
                            ->multiple()
                            ->default($this->selectedCampaignIds)
                            ->disabled()
                            ->helperText('Estas son las campañas configuradas en la cuenta de Facebook'),
                            
                        Select::make('selectedAdIds')
                            ->label('Anuncios Configurados')
                            ->options($this->getAdOptions())
                            ->multiple()
                            ->default($this->selectedAdIds)
                            ->disabled()
                            ->helperText('Estos son los anuncios específicos configurados para sincronización'),
                            
                        Select::make('dateRange')
                            ->label('Período de Datos')
                            ->options([
                                'last_7d' => 'Últimos 7 días',
                                'last_14d' => 'Últimos 14 días',
                                'last_30d' => 'Últimos 30 días',
                                'last_90d' => 'Últimos 90 días',
                                'this_month' => 'Este mes',
                                'last_month' => 'Mes pasado',
                            ])
                            ->default('last_7d')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if (!empty($this->selectedAdIds)) {
                                    $this->loadAdsStats();
                                }
                            }),
                    ])->columns(1),
            ]);
    }

    public function loadConfiguredData(): void
    {
        try {
            $this->isLoading = true;
            
            // Cargar información de las campañas configuradas
            $this->loadCampaignsInfo();
            
            // Si hay anuncios configurados, cargar sus estadísticas
            if (!empty($this->selectedAdIds)) {
                $this->loadAdsStats();
            }
            
            $this->isLoading = false;
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            Log::error('Error cargando datos configurados: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('Error al cargar los datos configurados: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function loadCampaignsInfo(): void
    {
        try {
            // Inicializar Facebook API
            Api::init(
                $this->record->app_id,
                $this->record->app_secret,
                $this->record->access_token
            );
            
            $account = new AdAccount('act_' . $this->record->selected_ad_account_id);
            
            // Obtener información de las campañas configuradas
            if (!empty($this->selectedCampaignIds)) {
                $campaigns = $account->getCampaigns(['id', 'name', 'status'], [
                    'filtering' => [
                        [
                            'field' => 'id',
                            'operator' => 'IN',
                            'value' => $this->selectedCampaignIds,
                        ],
                    ],
                ]);
                
                $this->campaigns = collect($campaigns)->map(function ($campaign) {
                    return [
                        'id' => $campaign->id,
                        'name' => $campaign->name,
                        'status' => $campaign->status,
                    ];
                })->toArray();
            }
            
        } catch (\Exception $e) {
            Log::error('Error cargando información de campañas: ' . $e->getMessage());
            throw $e;
        }
    }

    public function loadAdsStats(): void
    {
        try {
            $this->isLoading = true;
            
            // Inicializar Facebook API
            Api::init(
                $this->record->app_id,
                $this->record->app_secret,
                $this->record->access_token
            );
            
            $account = new AdAccount('act_' . $this->record->selected_ad_account_id);
            
            // Obtener estadísticas de los anuncios específicos configurados
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
                'time_range' => ['since' => $this->getDateRangeStart($this->dateRange), 'until' => now()->format('Y-m-d')],
                'filtering' => [
                    [
                        'field' => 'ad.id',
                        'operator' => 'IN',
                        'value' => $this->selectedAdIds,
                    ],
                ],
            ];

            $insights = $account->getInsights($fields, $params);
            
            // Obtener información creativa de los anuncios
            $adIds = collect($insights)->pluck('ad_id')->filter()->toArray();
            $creatives = [];
            
            try {
                $creatives = $this->getAdCreatives($account, $adIds);
            } catch (\Exception $e) {
                Log::warning('Error obteniendo creativos, continuando sin imágenes: ' . $e->getMessage());
            }
            
            $this->ads = collect($insights)->map(function ($insight) use ($creatives) {
                // Procesar interacciones
                $actions = $insight->actions ?? [];
                $interactions = $this->processInteractions($actions);
                
                // Procesar videos vistos
                $videoViews = $this->processVideoViews($insight);
                
                $creative = $creatives[$insight->ad_id ?? ''] ?? null;
                
                return [
                    'ad_id' => $insight->ad_id ?? null,
                    'ad_name' => $insight->ad_name ?? 'Sin nombre',
                    'campaign_name' => $insight->campaign_name ?? 'Sin campaña',
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
            })->toArray();
            
            $this->isLoading = false;
            
            Notification::make()
                ->title('Datos Cargados')
                ->body('Se cargaron ' . count($this->ads) . ' anuncios con sus estadísticas')
                ->success()
                ->send();
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            Log::error('Error cargando estadísticas de anuncios: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('Error al cargar las estadísticas: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getCampaignOptions(): array
    {
        return collect($this->campaigns)->pluck('name', 'id')->toArray();
    }

    public function getAdOptions(): array
    {
        // Crear opciones de anuncios basadas en los IDs configurados
        $options = [];
        foreach ($this->selectedAdIds as $adId) {
            $options[$adId] = "Anuncio ID: {$adId}";
        }
        return $options;
    }

    private function getDateRangeStart(string $dateRange): string
    {
        return match($dateRange) {
            'last_7d' => now()->subDays(7)->format('Y-m-d'),
            'last_14d' => now()->subDays(14)->format('Y-m-d'),
            'last_30d' => now()->subDays(30)->format('Y-m-d'),
            'last_90d' => now()->subDays(90)->format('Y-m-d'),
            'this_month' => now()->startOfMonth()->format('Y-m-d'),
            'last_month' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            default => now()->subDays(7)->format('Y-m-d'),
        };
    }

    public function getTitle(): string
    {
        // Asegurar que tenemos el objeto FacebookAccount
        if (is_string($this->record)) {
            $this->record = FacebookAccount::findOrFail($this->record);
        }
        
        return "Campañas de {$this->record->account_name}";
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
     * Obtiene información creativa de los anuncios usando el campo 'creative' directamente
     */
    private function getAdCreatives($account, $adIds): array
    {
        $creatives = [];
        
        try {
            // Para cada anuncio, obtener su creative directamente
            foreach ($adIds as $adId) {
                try {
                    $ad = new \FacebookAds\Object\Ad($adId);
                    
                    // Obtener información del anuncio incluyendo creative
                    $adData = $ad->getSelf(['id', 'name', 'creative']);
                    
                    // Debug: Log para ver qué datos obtenemos
                    Log::info("Datos del anuncio {$adId}:", [
                        'has_creative' => isset($adData->creative),
                        'creative_data' => $adData->creative ?? 'no_data'
                    ]);
                    
                    if (isset($adData->creative) && is_array($adData->creative) && isset($adData->creative['id'])) {
                        $creativeId = $adData->creative['id'];
                        
                        // Obtener información completa del creative usando su ID
                        try {
                            $creative = new \FacebookAds\Object\AdCreative($creativeId);
                            $creativeData = $creative->getSelf(['id', 'name', 'image_url', 'image_hash', 'thumbnail_url', 'body', 'title', 'object_story_spec']);
                            
                            $creativeInfo = [
                                'id' => $creativeData->id ?? null,
                                'name' => $creativeData->name ?? null,
                                'image_url' => $creativeData->image_url ?? null,
                                'image_hash' => $creativeData->image_hash ?? null,
                                'thumbnail_url' => $creativeData->thumbnail_url ?? null,
                                'body' => $creativeData->body ?? null,
                                'title' => $creativeData->title ?? null,
                                'local_image_path' => null,
                            ];
                            
                            // Intentar descargar y guardar la imagen localmente
                            $imageUrl = $creativeInfo['image_url'] ?? $creativeInfo['thumbnail_url'];
                            if ($imageUrl) {
                                $localPath = $this->downloadAndSaveImage($imageUrl, $adId);
                                if ($localPath) {
                                    $creativeInfo['local_image_path'] = $localPath;
                                    Log::info("Imagen descargada para anuncio {$adId}: {$localPath}");
                                }
                            }
                            
                            $creatives[$adId] = $creativeInfo;
                            Log::info("Creative completo obtenido para anuncio {$adId}");
                        } catch (\Exception $e) {
                            Log::warning("Error obteniendo creative completo para anuncio {$adId}: " . $e->getMessage());
                        }
                    } else {
                        Log::warning("Anuncio {$adId} no tiene creative o creative no es un array válido");
                    }
                } catch (\Exception $e) {
                    Log::warning("Error obteniendo creative para anuncio {$adId}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error general obteniendo creativos: ' . $e->getMessage());
        }
        
        Log::info("Total de creativos obtenidos: " . count($creatives));
        return $creatives;
    }
    
    /**
     * Descarga y guarda una imagen localmente
     */
    private function downloadAndSaveImage($imageUrl, $adId): ?string
    {
        try {
            // Crear directorio si no existe
            $directory = storage_path('app/public/ad-images');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Generar nombre único para la imagen
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = "ad_{$adId}_" . time() . ".{$extension}";
            $localPath = $directory . '/' . $filename;
            
            // Verificar si ya existe la imagen
            $existingFiles = glob($directory . "/ad_{$adId}_*");
            if (!empty($existingFiles)) {
                // Usar imagen existente
                $existingFile = basename($existingFiles[0]);
                return "storage/ad-images/{$existingFile}";
            }
            
            // Descargar la imagen
            $imageContent = file_get_contents($imageUrl);
            
            if ($imageContent !== false) {
                file_put_contents($localPath, $imageContent);
                return "storage/ad-images/{$filename}";
            }
        } catch (\Exception $e) {
            Log::warning("Error descargando imagen para anuncio {$adId}: " . $e->getMessage());
        }
        
        return null;
    }
} 