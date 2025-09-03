<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignReconciliationResource\Pages;
use App\Models\CampaignReconciliation;
use App\Models\FacebookAccount;
use App\Models\AdvertisingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;

class CampaignReconciliationResource extends Resource
{
    protected static ?string $model = CampaignReconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Conciliación de Campañas';

    protected static ?string $modelLabel = 'Conciliación de Campaña';

    protected static ?string $pluralModelLabel = 'Conciliaciones de Campañas';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración de Acceso a Meta')
                    ->description('Configura los datos de acceso para obtener campañas automáticamente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('app_id')
                                    ->label('App ID')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('123456789012345')
                                    ->live(),

                                TextInput::make('app_secret')
                                    ->label('App Secret')
                                    ->required()
                                    ->password()
                                    ->maxLength(255)
                                    ->live()
                                    ->visible(fn ($get) => !empty($get('app_id'))),

                                TextInput::make('access_token')
                                    ->label('Access Token')
                                    ->required()
                                    ->placeholder('EAA...')
                                    ->live()
                                    ->visible(fn ($get) => !empty($get('app_id')) && !empty($get('app_secret')))
                                    ->afterStateUpdated(function ($state, $set) {
                                        // Limpiar todos los campos cuando cambie el token
                                        $set('selected_ad_account_id', null);
                                        $set('selected_page_id', null);
                                        $set('selected_campaign_ids', []);
                                        $set('selected_ad_ids', []);
                                    }),
                            ]),
                    ]),

                Section::make('Selección de Cuenta y Páginas')
                    ->description('Selecciona la cuenta publicitaria y las páginas para filtrar campañas')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('selected_ad_account_id')
                                    ->label('Cuenta Publicitaria')
                                    ->helperText('Selecciona la cuenta publicitaria que quieres usar.')
                                    ->placeholder('Selecciona una cuenta publicitaria')
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->options(function ($get) {
                                        $accountOptions = $get('account_options') ?? [];
                                        return $accountOptions;
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('selected_facebook_pages', []);
                                        $set('selected_instagram_accounts', []);
                                        $set('selected_campaign_ids', []);
                                        $set('selected_ad_ids', []);
                                    })
                                    ->suffixAction(
                                        Action::make('refresh_ad_accounts')
                                            ->label('Refrescar')
                                            ->icon('heroicon-o-arrow-path')
                                            ->color('info')
                                            ->action(function ($state, $set, $get) {
                                                $appId = $get('app_id');
                                                $appSecret = $get('app_secret');
                                                $accessToken = $get('access_token');
                                                
                                                if (!$appId || !$appSecret || !$accessToken) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Completa App ID, App Secret y Access Token antes de refrescar las cuentas.')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }
                                                
                                                try {
                                                    Notification::make()
                                                        ->title('Cargando cuentas...')
                                                        ->body('Obteniendo cuentas publicitarias de Facebook. Esto puede tomar unos segundos.')
                                                        ->info()
                                                        ->send();
                                                    
                                                    $url = "https://graph.facebook.com/v18.0/me/adaccounts?limit=250&access_token={$accessToken}";
                                                    $response = file_get_contents($url);
                                                    $data = json_decode($response, true);
                                                    
                                                    if (!isset($data['data'])) {
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('No se pudieron obtener las cuentas publicitarias')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    $accountOptions = [];
                                                    foreach ($data['data'] as $account) {
                                                        $accountId = str_replace('act_', '', $account['id']);
                                                        $accountName = $account['name'] ?? 'Cuenta ' . $accountId;
                                                        $accountOptions[$accountId] = $accountName . ' (ID: ' . $accountId . ')';
                                                    }
                                                    
                                                    $set('account_options', $accountOptions);
                                                    $set('selected_ad_account_id', null);
                                                    $set('facebook_page_options', []);
                                                    $set('selected_facebook_pages', []);
                                                    $set('instagram_account_options', []);
                                                    $set('selected_instagram_accounts', []);
                                                    $set('campaign_options', []);
                                                    $set('selected_campaign_ids', []);
                                                    $set('ad_options', []);
                                                    $set('selected_ad_ids', []);
                                                    
                                                    Notification::make()
                                                        ->title('Cuentas Actualizadas')
                                                        ->body("Se encontraron " . count($accountOptions) . " cuentas publicitarias en tu cuenta de Facebook")
                                                        ->success()
                                                        ->send();
                                                        
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Error obteniendo cuentas: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),

                               
                            ]),
                    ]),

                                Section::make('Todas las Campañas Activas')
                    ->description('Visualiza todas las campañas activas de la cuenta publicitaria')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Select::make('selected_campaign_ids')
                                    ->label('Campañas Activas de la Cuenta')
                                    ->helperText('Selecciona las campañas que quieres conciliar automáticamente.')
                                    ->placeholder('Selecciona campañas')
                                    ->multiple()
                                    ->searchable()
                                    ->visible(fn ($get) => !empty($get('selected_ad_account_id')))
                                    ->live()
                                    ->options(function ($get) {
                                        $campaignOptions = $get('campaign_options') ?? [];
                                        return $campaignOptions;
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('selected_ad_ids', []);
                                    })
                                    ->suffixAction(
                                        Action::make('refresh_all_campaigns')
                                            ->label('🔍 Ver Todas las Campañas')
                                            ->icon('heroicon-o-magnifying-glass')
                                            ->color('success')
                                            ->action(function ($state, $set, $get) {
                                                $accessToken = $get('access_token');
                                                $adAccountId = $get('selected_ad_account_id');
                                                
                                                if (!$accessToken || !$adAccountId) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Completa el Access Token y la cuenta publicitaria.')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }
                                                
                                                try {
                                                    Notification::make()
                                                        ->title('🔍 Cargando todas las campañas...')
                                                        ->body('Obteniendo todas las campañas activas de la cuenta publicitaria.')
                                                        ->info()
                                                        ->send();
                                                    
                                                    // Obtener TODAS las campañas activas de la cuenta publicitaria
                                                    $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time,objective,budget_remaining,budget&limit=250&access_token={$accessToken}";
                                                    $campaignsResponse = file_get_contents($campaignsUrl);
                                                    $campaignsData = json_decode($campaignsResponse, true);
                                                    
                                                    if (!isset($campaignsData['data'])) {
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('No se pudieron obtener las campañas')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    // Filtrar solo campañas activas
                                                    $activeCampaigns = [];
                                                    foreach ($campaignsData['data'] as $campaign) {
                                                        if ($campaign['status'] == 'ACTIVE') {
                                                            $activeCampaigns[] = $campaign;
                                                        }
                                                    }
                                                    
                                                    $campaignOptions = [];
                                                    foreach ($activeCampaigns as $campaign) {
                                                        $dailyBudget = isset($campaign['daily_budget']) ? $campaign['daily_budget'] : null;
                                                        $budgetText = 'Sin presupuesto diario';
                                                        
                                                        if ($dailyBudget !== null && is_numeric($dailyBudget)) {
                                                            // Meta devuelve presupuestos en centavos, convertir a dólares
                                                            if ($dailyBudget > 1000) {
                                                                $dailyBudget = $dailyBudget / 100;
                                                            }
                                                            $budgetText = '$' . number_format($dailyBudget, 2) . ' diario';
                                                        }
                                                        
                                                        $campaignOptions[$campaign['id']] = $campaign['name'] . ' (ID: ' . $campaign['id'] . ') - ' . $budgetText;
                                                    }
                                                    
                                                    $set('campaign_options', $campaignOptions);
                                                    $set('selected_campaign_ids', []);
                                                    
                                                    $totalCampaigns = count($activeCampaigns);
                                                    
                                                    Notification::make()
                                                        ->title('✅ Campañas Cargadas')
                                                        ->body("Se encontraron {$totalCampaigns} campañas activas en la cuenta publicitaria.")
                                                        ->success()
                                                        ->send();
                                                        
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Error cargando campañas: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),
                            ]),
                    ]),

                Section::make('Filtrado por Páginas Específicas')
                    ->description('Filtra campañas por fan pages o cuentas de Instagram específicas')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('selected_facebook_pages')
                                    ->label('Fan Pages de Facebook')
                                    ->helperText('Selecciona las fan pages de Facebook para filtrar.')
                                    ->placeholder('Selecciona fan pages')
                                    ->multiple()
                                    ->searchable()
                                    ->visible(fn ($get) => !empty($get('selected_ad_account_id')))
                                    ->live()
                                    ->options(function ($get) {
                                        $facebookPageOptions = $get('facebook_page_options') ?? [];
                                        return $facebookPageOptions;
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('filtered_campaign_options', []);
                                        $set('selected_ad_ids', []);
                                    })
                                    ->suffixAction(
                                        Action::make('refresh_facebook_pages')
                                            ->label('Refrescar Fan Pages')
                                            ->icon('heroicon-o-building-storefront')
                                            ->color('blue')
                                            ->action(function ($state, $set, $get) {
                                                $accessToken = $get('access_token');
                                                
                                                if (!$accessToken) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Completa el Access Token antes de refrescar las fan pages.')
                                                        ->danger()
                                                        ->send();
                                                        return;
                                                }
                                                
                                                try {
                                                    Notification::make()
                                                        ->title('Cargando fan pages...')
                                                        ->body('Obteniendo páginas de Facebook. Esto puede tomar unos segundos.')
                                                        ->info()
                                                        ->send();
                                                    
                                                    $url = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$accessToken}";
                                                    $response = file_get_contents($url);
                                                    $data = json_decode($response, true);
                                                    
                                                    $facebookPageOptions = [];
                                                    if (isset($data['data'])) {
                                                        foreach ($data['data'] as $page) {
                                                            $facebookPageOptions[$page['id']] = $page['name'] . ' (Facebook - ' . ($page['category'] ?? 'Sin categoría') . ')';
                                                        }
                                                    }
                                                    
                                                    if (empty($facebookPageOptions)) {
                                                        Notification::make()
                                                            ->title('Error')
                                                            ->body('No se pudieron obtener las fan pages')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    $set('facebook_page_options', $facebookPageOptions);
                                                    $set('selected_facebook_pages', []);
                                                    $set('filtered_campaign_options', []);
                                                    $set('selected_ad_ids', []);
                                                    
                                                    Notification::make()
                                                        ->title('Fan Pages Actualizadas')
                                                        ->body("Se encontraron " . count($facebookPageOptions) . " fan pages de Facebook")
                                                        ->success()
                                                        ->send();
                                                        
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Error obteniendo fan pages: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),

                                Select::make('selected_instagram_accounts')
                                    ->label('Cuentas de Instagram Business')
                                    ->helperText('Selecciona las cuentas de Instagram Business para filtrar.')
                                    ->placeholder('Selecciona cuentas de Instagram')
                                    ->multiple()
                                    ->searchable()
                                    ->visible(fn ($get) => !empty($get('selected_ad_account_id')))
                                    ->live()
                                    ->options(function ($get) {
                                        $instagramAccountOptions = $get('instagram_account_options') ?? [];
                                        return $instagramAccountOptions;
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('filtered_campaign_options', []);
                                        $set('selected_ad_ids', []);
                                    })
                                    ->suffixAction(
                                        Action::make('refresh_instagram_accounts')
                                            ->label('Refrescar Instagram')
                                            ->icon('heroicon-o-camera')
                                            ->color('pink')
                                            ->action(function ($state, $set, $get) {
                                                $accessToken = $get('access_token');
                                                
                                                if (!$accessToken) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Completa el Access Token antes de refrescar las cuentas de Instagram.')
                                                        ->danger()
                                                        ->send();
                                                        return;
                                                }
                                                
                                                try {
                                                    Notification::make()
                                                        ->title('Cargando cuentas de Instagram...')
                                                        ->body('Obteniendo cuentas de Instagram Business. Esto puede tomar unos segundos.')
                                                        ->info()
                                                        ->send();
                                                    
                                                    $instagramUrl = "https://graph.facebook.com/v18.0/me/accounts?type=instagram&limit=250&access_token={$accessToken}";
                                                    $instagramResponse = @file_get_contents($instagramUrl);
                                                    $instagramAccountOptions = [];
                                                    
                                                    if ($instagramResponse !== false) {
                                                        $instagramData = json_decode($instagramResponse, true);
                                                        if (isset($instagramData['data'])) {
                                                            foreach ($instagramData['data'] as $instagram) {
                                                                $instagramAccountOptions[$instagram['id']] = $instagram['name'] . ' (Instagram Business)';
                                                            }
                                                        }
                                                    }
                                                    
                                                    if (empty($instagramAccountOptions)) {
                                                        Notification::make()
                                                            ->title('Info')
                                                            ->body('No se encontraron cuentas de Instagram Business')
                                                            ->info()
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    $set('instagram_account_options', $instagramAccountOptions);
                                                    $set('selected_instagram_accounts', []);
                                                    $set('filtered_campaign_options', []);
                                                    $set('selected_ad_ids', []);
                                                    
                                                    Notification::make()
                                                        ->title('Cuentas de Instagram Actualizadas')
                                                        ->body("Se encontraron " . count($instagramAccountOptions) . " cuentas de Instagram Business")
                                                        ->success()
                                                        ->send();
                                                        
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Error obteniendo cuentas de Instagram: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Select::make('filtered_campaign_ids')
                                    ->label('Campañas Filtradas por Páginas')
                                    ->helperText('Campañas específicas de las páginas seleccionadas (opcional)')
                                    ->placeholder('Selecciona campañas filtradas')
                                    ->multiple()
                                    ->searchable()
                                    ->visible(fn ($get) => 
                                        (!empty($get('selected_facebook_pages') || !empty($get('selected_instagram_accounts'))) &&
                                        !empty($get('selected_campaign_ids'))
                                    )) 
                                    ->live()
                                    ->options(function ($get) {
                                        $filteredCampaignOptions = $get('filtered_campaign_options') ?? [];
                                        return $filteredCampaignOptions;
                                    })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('selected_ad_ids', []);
                                    })
                                    ->suffixAction(
                                        Action::make('filter_campaigns_by_pages')
                                            ->label('🔍 Filtrar por Páginas')
                                            ->icon('heroicon-o-funnel')
                                            ->color('info')
                                            ->action(function ($state, $set, $get) {
                                                $selectedFacebookPages = $get('selected_facebook_pages') ?? [];
                                                $selectedInstagramAccounts = $get('selected_instagram_accounts') ?? [];
                                                $selectedCampaignIds = $get('selected_campaign_ids') ?? [];
                                                $accessToken = $get('access_token');
                                                $adAccountId = $get('selected_ad_account_id');
                                                
                                                if (empty($selectedFacebookPages) && empty($selectedInstagramAccounts)) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Debes seleccionar al menos una fan page o cuenta de Instagram.')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }
                                                
                                                if (empty($selectedCampaignIds)) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Debes seleccionar campañas primero.')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }
                                                
                                                try {
                                                    Notification::make()
                                                        ->title('🔍 Filtrando campañas por páginas...')
                                                        ->body('Analizando qué campañas corresponden a las páginas seleccionadas.')
                                                        ->info()
                                                        ->send();
                                                    
                                                    // Obtener anuncios y filtrar por páginas seleccionadas
                                                    $adsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads?fields=id,name,campaign_id,creative,status&limit=250&access_token={$accessToken}";
                                                    $adsResponse = @file_get_contents($adsUrl);
                                                    $adsData = json_decode($adsResponse, true);
                                                    
                                                    $campaignsForPages = [];
                                                    $processedAds = 0;
                                                    $allSelectedPages = array_merge($selectedFacebookPages, $selectedInstagramAccounts);
                                                    
                                                    if (isset($adsData['data'])) {
                                                        foreach ($adsData['data'] as $ad) {
                                                            if (!in_array($ad['campaign_id'], $selectedCampaignIds)) {
                                                                continue;
                                                            }
                                                            
                                                            $processedAds++;
                                                            if (isset($ad['creative']['id'])) {
                                                                $creativeId = $ad['creative']['id'];
                                                                $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$accessToken}";
                                                                $creativeResponse = @file_get_contents($creativeUrl);
                                                                if ($creativeResponse !== false) {
                                                                    $creativeData = json_decode($creativeResponse, true);
                                                                    
                                                                    if (isset($creativeData['object_story_spec']['page_id']) && 
                                                                        in_array($creativeData['object_story_spec']['page_id'], $allSelectedPages)) {
                                                                        $campaignsForPages[$ad['campaign_id']] = true;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Obtener campañas filtradas
                                                    $filteredCampaigns = [];
                                                    $campaignOptions = $get('campaign_options') ?? [];
                                                    
                                                    foreach ($selectedCampaignIds as $campaignId) {
                                                        if (isset($campaignsForPages[$campaignId])) {
                                                            $filteredCampaigns[] = $campaignId;
                                                        }
                                                    }
                                                    
                                                    $filteredCampaignOptions = [];
                                                    foreach ($filteredCampaigns as $campaignId) {
                                                        if (isset($campaignOptions[$campaignId])) {
                                                            $filteredCampaignOptions[$campaignId] = $campaignOptions[$campaignId];
                                                        }
                                                    }
                                                    
                                                    $set('filtered_campaign_options', $filteredCampaignOptions);
                                                    $set('filtered_campaign_ids', []);
                                                    
                                                    $totalPages = count($allSelectedPages);
                                                    $totalFiltered = count($filteredCampaigns);
                                                    
                                                    Notification::make()
                                                        ->title('✅ Filtrado Completado')
                                                        ->body("Se encontraron {$totalFiltered} campañas de {$totalPages} páginas seleccionadas. Procesados {$processedAds} anuncios.")
                                                        ->success()
                                                        ->send();
                                                        
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('Error filtrando campañas: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),
                            ]),
                    ]),

                        // Campos ocultos para almacenar las opciones
                        Hidden::make('account_options'),
                        Hidden::make('facebook_page_options'),
                        Hidden::make('instagram_account_options'),
                        Hidden::make('campaign_options'),
                        Hidden::make('filtered_campaign_options'),
                        Hidden::make('ad_options'),
                    

                Section::make('Acciones Automáticas')
                    ->description('Utiliza la inteligencia del sistema para detectar y conciliar automáticamente')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('auto_detect_trigger')
                                    ->label('🚀 DETECTAR Y CONCILIAR AUTOMÁTICAMENTE')
                                    ->placeholder('Presiona el botón de refrescar para ejecutar la detección automática')
                                    ->disabled()
                                    ->suffixAction(
                                        Action::make('auto_detect_and_reconcile')
                                            ->label('🚀 EJECUTAR')
                                            ->icon('heroicon-o-sparkles')
                                            ->color('success')
                                            ->size('lg')
                                            ->action(function ($state, $set, $get) {
                                                try {
                                                                                                // Validar que tengamos todos los datos necesarios
                                            $selectedCampaignIds = $get('selected_campaign_ids');
                                            $selectedFacebookPages = $get('selected_facebook_pages') ?? [];
                                            $selectedInstagramAccounts = $get('selected_instagram_accounts') ?? [];
                                            $accessToken = $get('access_token');
                                            $adAccountId = $get('selected_ad_account_id');
                                                    
                                            if (empty($selectedCampaignIds) || empty($accessToken) || empty($adAccountId)) {
                                                Notification::make()
                                                    ->title('Error')
                                                    ->body('Debes seleccionar al menos una campaña y tener acceso configurado.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                                    
                                                    Notification::make()
                                                        ->title('🔍 Analizando campañas...')
                                                        ->body('Detectando planes de publicidad y creando conciliaciones automáticamente.')
                                                        ->info()
                                                        ->send();
                                                    
                                                    $reconciliationsCreated = 0;
                                                    $errors = 0;
                                                    
                                                    foreach ($selectedCampaignIds as $campaignId) {
                                                        try {
                                                            // Obtener detalles completos de la campaña desde Meta
                                                            $campaignUrl = "https://graph.facebook.com/v18.0/{$campaignId}?fields=id,name,status,daily_budget,lifetime_budget,start_time,stop_time,objective,insights{spend,impressions,reach}&access_token={$accessToken}";
                                                            $campaignResponse = file_get_contents($campaignUrl);
                                                            $campaignData = json_decode($campaignResponse, true);
                                                            
                                                            if (!isset($campaignData['id'])) {
                                                                $errors++;
                                                                continue;
                                                            }
                                                            
                                                            // Extraer información de la campaña
                                                            $campaignInfo = self::extractCampaignInfoFromMeta($campaignData);
                                                            
                                                            // Detectar plan automáticamente
                                                            $detectedPlan = self::detectAdvertisingPlan($campaignInfo);
                                                            
                                                            // Determinar tipo de cliente basado en las páginas seleccionadas
                                                            $clientType = 'fanpage';
                                                            if (!empty($selectedInstagramAccounts) && empty($selectedFacebookPages)) {
                                                                $clientType = 'instagram';
                                                            } elseif (!empty($selectedInstagramAccounts) && !empty($selectedFacebookPages)) {
                                                                $clientType = 'both';
                                                            }
                                                            
                                                            // Obtener insights de gasto real si están disponibles
                                                            $actualSpend = 0;
                                                            if (isset($campaignData['insights']['data'][0]['spend'])) {
                                                                $actualSpend = (float) $campaignData['insights']['data'][0]['spend'];
                                                            }
                                                            
                                                            // Crear la conciliación
                                                            $reconciliation = CampaignReconciliation::create([
                                                                'facebook_account_id' => null, // Se puede actualizar después
                                                                'meta_campaign_id' => $campaignId,
                                                                'meta_campaign_name' => $campaignData['name'] ?? 'Campaña ' . $campaignId,
                                                                'client_name' => $campaignInfo['client_name'],
                                                                'client_type' => $clientType,
                                                                'daily_budget' => $campaignInfo['daily_budget'],
                                                                'duration_days' => $campaignInfo['duration_days'],
                                                                'total_budget' => $campaignInfo['total_budget'],
                                                                'client_price' => $detectedPlan ? $detectedPlan->client_price : 0,
                                                                'profit_margin' => $detectedPlan ? $detectedPlan->profit_margin : 0,
                                                                'actual_spend' => $actualSpend,
                                                                'remaining_budget' => max(0, $campaignInfo['total_budget'] - $actualSpend),
                                                                'status' => 'pending',
                                                                'campaign_start_date' => $campaignInfo['start_date'],
                                                                'campaign_end_date' => $campaignInfo['end_date'],
                                                                'advertising_plan_id' => $detectedPlan ? $detectedPlan->id : null,
                                                                'meta_data' => $campaignData,
                                                                'notes' => $detectedPlan 
                                                                    ? "Plan detectado automáticamente: {$detectedPlan->plan_name}"
                                                                    : "Campaña detectada automáticamente - Plan no identificado"
                                                            ]);
                                                            
                                                            $reconciliationsCreated++;
                                                            
                                                            // Actualizar campos del formulario con la primera campaña
                                                            if ($reconciliationsCreated === 1) {
                                                                $set('meta_campaign_id', $campaignId);
                                                                $set('meta_campaign_name', $campaignData['name'] ?? 'Campaña ' . $campaignId);
                                                                $set('daily_budget', $campaignInfo['daily_budget']);
                                                                $set('duration_days', $campaignInfo['duration_days']);
                                                                $set('total_budget', $campaignInfo['total_budget']);
                                                                $set('client_name', $campaignInfo['client_name']);
                                                                $set('client_price', $detectedPlan ? $detectedPlan->client_price : 0);
                                                                $set('profit_margin', $detectedPlan ? $detectedPlan->profit_margin : 0);
                                                                $set('remaining_budget', $campaignInfo['total_budget']);
                                                                $set('campaign_start_date', $campaignInfo['start_date']);
                                                                $set('campaign_end_date', $campaignInfo['end_date']);
                                                                $set('advertising_plan_id', $detectedPlan ? $detectedPlan->id : null);
                                                            }
                                                            
                                                        } catch (\Exception $e) {
                                                            $errors++;
                                                            \Illuminate\Support\Facades\Log::error("Error procesando campaña {$campaignId}: " . $e->getMessage());
                                                        }
                                                    }
                                                    
                                                    if ($reconciliationsCreated > 0) {
                                                        Notification::make()
                                                            ->title('✅ Conciliación Exitosa')
                                                            ->body("Se crearon {$reconciliationsCreated} conciliaciones automáticamente." . ($errors > 0 ? " {$errors} errores." : ""))
                                                            ->success()
                                                            ->send();
                                                    } else {
                                                        Notification::make()
                                                            ->title('❌ Error')
                                                            ->body('No se pudo crear ninguna conciliación. Revisa los logs.')
                                                            ->danger()
                                                            ->send();
                                                    }
                                                    
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('❌ Error Fatal')
                                                        ->body('Error en la detección automática: ' . $e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                    
                                                    \Illuminate\Support\Facades\Log::error("Error en detección automática: " . $e->getMessage());
                                                }
                                            })
                                            ->requiresConfirmation()
                                            ->modalHeading('🚀 Detección Automática de Planes')
                                            ->modalDescription('El sistema analizará las campañas seleccionadas y detectará automáticamente los planes de publicidad correspondientes. ¿Continuar?')
                                            ->modalSubmitActionLabel('¡SÍ, DETECTAR AUTOMÁTICAMENTE!')
                                            ->modalCancelActionLabel('Cancelar')
                                            ->visible(fn ($get) => !empty($get('selected_campaign_ids')))
                                    ),
                            ]),
                    ]),

                Section::make('Información de Meta Ads')
                    ->description('Datos de la campaña en Facebook/Meta (se llenan automáticamente)')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('meta_campaign_id')
                                    ->label('ID de Campaña Meta')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Se selecciona automáticamente')
                                    ->helperText('ID único de la campaña en Meta Ads'),

                                TextInput::make('meta_campaign_name')
                                    ->label('Nombre de Campaña Meta')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Se obtiene automáticamente')
                                    ->helperText('Nombre de la campaña en Meta Ads'),

                                TextInput::make('meta_adset_id')
                                    ->label('ID de Conjunto de Anuncios')
                                    ->maxLength(255)
                                    ->placeholder('Opcional'),

                                TextInput::make('meta_ad_id')
                                    ->label('ID de Anuncio')
                                    ->maxLength(255)
                                    ->placeholder('Opcional'),
                            ]),
                    ]),

                Section::make('Información del Cliente')
                    ->description('Datos del cliente y tipo de campaña')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('client_name')
                                    ->label('Nombre del Cliente')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('BrandShop')
                                    ->helperText('Nombre de la empresa o marca del cliente'),

                                Select::make('client_type')
                                    ->label('Tipo de Cliente')
                                    ->required()
                                    ->options([
                                        'fanpage' => 'Fan Page',
                                        'instagram' => 'Instagram',
                                        'both' => 'Facebook + Instagram',
                                        'other' => 'Otro',
                                    ])
                                    ->default('fanpage')
                                    ->placeholder('Selecciona el tipo de cliente'),
                            ]),
                    ]),

                Section::make('Configuración de Presupuesto')
                    ->description('Presupuesto y duración de la campaña')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('daily_budget')
                                    ->label('Presupuesto Diario ($)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('3.00')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calculateTotalBudget($set, $get);
                                    }),

                                TextInput::make('duration_days')
                                    ->label('Duración (Días)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->step(1)
                                    ->placeholder('7')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calculateTotalBudget($set, $get);
                                    }),

                                TextInput::make('total_budget')
                                    ->label('Presupuesto Total ($)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('21.00')
                                    ->disabled()
                                    ->helperText('Calculado automáticamente'),
                            ]),
                    ]),

                Section::make('Plan de Publicidad')
                    ->description('Plan detectado automáticamente o seleccionado manualmente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('advertising_plan_id')
                                    ->label('Plan de Publicidad')
                                    ->options(AdvertisingPlan::active()->pluck('plan_name', 'id'))
                                    ->searchable()
                                    ->placeholder('Selecciona un plan o deja que se detecte automáticamente')
                                    ->helperText('Si no seleccionas un plan, el sistema intentará detectarlo automáticamente')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::updatePlanDetails($set, $get);
                                    }),

                                TextInput::make('client_price')
                                    ->label('Precio al Cliente ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('29.00')
                                    ->helperText('Precio que paga el cliente por este plan'),

                                TextInput::make('profit_margin')
                                    ->label('Ganancia ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('8.00')
                                    ->helperText('Ganancia esperada de esta campaña'),

                                TextInput::make('remaining_budget')
                                    ->label('Presupuesto Restante ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('21.00')
                                    ->helperText('Presupuesto restante disponible'),
                            ]),
                    ]),

                Section::make('Estado y Seguimiento')
                    ->description('Control del estado de la campaña')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Estado')
                                    ->required()
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'active' => 'Activa',
                                        'completed' => 'Completada',
                                        'cancelled' => 'Cancelada',
                                    ])
                                    ->default('pending')
                                    ->placeholder('Selecciona el estado'),

                                TextInput::make('actual_spend')
                                    ->label('Gasto Real ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->helperText('Gasto real en Meta Ads (se actualiza automáticamente)')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::updateRemainingBudget($set, $get);
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('campaign_start_date')
                                    ->label('Fecha de Inicio')
                                    ->maxDate(now())
                                    ->placeholder('Selecciona fecha de inicio'),

                                DatePicker::make('campaign_end_date')
                                    ->label('Fecha de Fin')
                                    ->minDate(fn (Get $get) => $get('campaign_start_date'))
                                    ->placeholder('Selecciona fecha de fin'),
                            ]),
                    ]),

                Section::make('Información Adicional')
                    ->description('Datos adicionales y notas')
                    ->schema([
                        KeyValue::make('meta_data')
                            ->label('Datos de Meta')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->addActionLabel('Agregar Campo')
                            ->deleteActionLabel('Eliminar Campo')
                            ->helperText('Datos adicionales de la API de Meta'),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Notas adicionales sobre la campaña...')
                            ->maxLength(1000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('facebookAccount.account_name')
                    ->label('Cuenta Facebook')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('client_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('meta_campaign_name')
                    ->label('Campaña Meta')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('daily_budget')
                    ->label('Presupuesto Diario')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Duración')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (int $state): string => "{$state} días"),

                TextColumn::make('total_budget')
                    ->label('Presupuesto Total')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('client_price')
                    ->label('Precio Cliente')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('profit_margin')
                    ->label('Ganancia')
                    ->money('USD')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('actual_spend')
                    ->label('Gasto Real')
                    ->money('USD')
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('remaining_budget')
                    ->label('Restante')
                    ->money('USD')
                    ->sortable()
                    ->color('info'),

                BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Pendiente',
                        'active' => 'Activa',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'active' => 'Activa',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ]),
                Tables\Filters\SelectFilter::make('facebook_account_id')
                    ->label('Cuenta Facebook')
                    ->options(FacebookAccount::pluck('account_name', 'id')),
            ])
            ->actions([
                Action::make('detect_plan')
                    ->label('Detectar Plan')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->action(function (CampaignReconciliation $record) {
                        try {
                            $service = new \App\Services\CampaignReconciliationService();
                            $detectedPlan = $service->detectAndReconcileCampaigns();
                            
                            Notification::make()
                                ->title('Plan Detectado')
                                ->body('Se ha detectado automáticamente el plan de publicidad')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Error detectando plan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (CampaignReconciliation $record) => $record->status === 'pending'),

                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaignReconciliations::route('/'),
            'create' => Pages\CreateCampaignReconciliation::route('/create'),
            'edit' => Pages\EditCampaignReconciliation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Calcular presupuesto total automáticamente
     */
    private static function calculateTotalBudget(Set $set, Get $get): void
    {
        $dailyBudget = (float) ($get('daily_budget') ?? 0);
        $durationDays = (int) ($get('duration_days') ?? 0);
        
        $totalBudget = $dailyBudget * $durationDays;
        $set('total_budget', $totalBudget);
        
        // Actualizar presupuesto restante
        $actualSpend = (float) ($get('actual_spend') ?? 0);
        $remainingBudget = max(0, $totalBudget - $actualSpend);
        $set('remaining_budget', $remainingBudget);
    }

    /**
     * Actualizar detalles del plan seleccionado
     */
    private static function updatePlanDetails(Set $set, Get $get): void
    {
        $planId = $get('advertising_plan_id');
        
        if ($planId) {
            $plan = AdvertisingPlan::find($planId);
            if ($plan) {
                $set('client_price', $plan->client_price);
                $set('profit_margin', $plan->profit_margin);
            }
        }
    }

    /**
     * Actualizar presupuesto restante
     */
    private static function updateRemainingBudget(Set $set, Get $get): void
    {
        $totalBudget = (float) ($get('total_budget') ?? 0);
        $actualSpend = (float) ($get('actual_spend') ?? 0);
        
        $remainingBudget = max(0, $totalBudget - $actualSpend);
        $set('remaining_budget', $remainingBudget);
    }

    /**
     * Extraer información de campaña desde Meta API
     */
    private static function extractCampaignInfoFromMeta(array $campaignData): array
    {
        // Extraer presupuesto diario
        $dailyBudget = $campaignData['daily_budget'] ?? 
                      $campaignData['budget_remaining'] ?? 
                      $campaignData['budget'] ?? 
                      0;

        // Convertir de centavos a dólares si es necesario
        if ($dailyBudget > 1000) {
            $dailyBudget = $dailyBudget / 100;
        }

        // Extraer presupuesto total
        $totalBudget = $campaignData['lifetime_budget'] ?? 
                      $campaignData['budget'] ?? 
                      $campaignData['budget_remaining'] ?? 
                      0;

        if ($totalBudget > 1000) {
            $totalBudget = $totalBudget / 100;
        }

        // Estimar duración
        $startDate = $campaignData['start_time'] ?? $campaignData['created_time'] ?? null;
        $endDate = $campaignData['stop_time'] ?? $campaignData['end_time'] ?? null;
        
        $durationDays = 7; // Por defecto
        if ($startDate && $endDate) {
            $durationDays = \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1;
        } elseif ($dailyBudget > 0 && $totalBudget > 0) {
            $durationDays = (int) ceil($totalBudget / $dailyBudget);
        }

        // Extraer nombre del cliente del nombre de la campaña
        $campaignName = $campaignData['name'] ?? '';
        $clientName = 'Cliente Sin Identificar';
        
        if (preg_match('/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)/', $campaignName, $matches)) {
            $clientName = $matches[1];
        }

        return [
            'daily_budget' => (float) $dailyBudget,
            'total_budget' => (float) $totalBudget,
            'duration_days' => $durationDays,
            'client_name' => $clientName,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Detectar automáticamente el plan de publicidad
     */
    private static function detectAdvertisingPlan(array $campaignInfo): ?\App\Models\AdvertisingPlan
    {
        $dailyBudget = $campaignInfo['daily_budget'];
        $durationDays = $campaignInfo['duration_days'];
        
        // Buscar planes que coincidan exactamente
        $matchingPlan = \App\Models\AdvertisingPlan::active()
            ->where('daily_budget', $dailyBudget)
            ->where('duration_days', $durationDays)
            ->first();
        
        if ($matchingPlan) {
            return $matchingPlan;
        }
        
        // Si no hay coincidencia exacta, buscar el más cercano
        $closestPlan = \App\Models\AdvertisingPlan::active()
            ->get()
            ->sortBy(function ($plan) use ($dailyBudget, $durationDays) {
                $budgetDiff = abs($plan->daily_budget - $dailyBudget);
                $durationDiff = abs($plan->duration_days - $durationDays);
                return $budgetDiff + $durationDiff;
            })
            ->first();
        
        // Solo usar si la diferencia es razonable
        if ($closestPlan) {
            $budgetDiff = abs($closestPlan->daily_budget - $dailyBudget);
            $durationDiff = abs($closestPlan->duration_days - $durationDays);
            
            if ($budgetDiff <= 1.00 && $durationDiff <= 2) {
                return $closestPlan;
            }
        }
        
        return null;
    }
}
