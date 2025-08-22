<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookAccountResource\Pages;

use App\Models\FacebookAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;

class FacebookAccountResource extends Resource
{
    protected static ?string $model = FacebookAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Cuentas Facebook';

    protected static ?string $modelLabel = 'Cuenta de Facebook';

    protected static ?string $pluralModelLabel = 'Cuentas de Facebook';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Cuenta')
                    ->description('Configura los datos de acceso a Facebook Ads')
                    ->schema([
                        Forms\Components\TextInput::make('account_name')
                            ->label('Nombre de la Cuenta')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Mi Cuenta de Facebook Ads'),
                        Forms\Components\TextInput::make('account_id')
                            ->label('ID de la Cuenta')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('123456789'),
                        Forms\Components\TextInput::make('app_id')
                            ->label('App ID')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('123456789012345'),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->required()
                            ->password()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('access_token')
                            ->label('Access Token')
                            ->required()
                            ->rows(3)
                            ->placeholder('EAA...')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar campaña seleccionada cuando cambie el token
                                $set('selected_campaign_id', null);
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración de Automatización')
                    ->description('Configura qué datos se sincronizarán')
                    ->schema([
                        Forms\Components\TextInput::make('selected_ad_account_id')
                            ->label('Cuenta Publicitaria para Sincronización')
                            ->helperText('ID de la cuenta publicitaria específica (dejar vacío para usar la cuenta principal)')
                            ->placeholder('Ej: 658326730301827')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('selected_page_id', null);
                                    $set('selected_campaign_ids', []);
                                    $set('selected_ad_ids', []);
                                }
                            })
                            ->suffixAction(
                                Action::make('test_connection')
                                    ->label('Probar Conexión')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('primary')
                                    ->action(function ($state, $set, $get) {
                                        if (empty($state)) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Primero ingresa el ID de la cuenta publicitaria')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        $appId = $get('app_id');
                                        $appSecret = $get('app_secret');
                                        $accessToken = $get('access_token');
                                        
                                        if (!$appId || !$appSecret || !$accessToken) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Completa App ID, App Secret y Access Token antes de probar la conexión.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            // Inicializar Facebook API con datos del formulario
                                            Api::init($appId, $appSecret, $accessToken);
                                            
                                            $account = new AdAccount('act_' . $state);
                                            $campaigns = $account->getCampaigns(['id', 'name', 'status'], ['limit' => 250]);
                                            
                                            $activeCampaigns = 0;
                                            foreach ($campaigns as $campaign) {
                                                if ($campaign->status == 'ACTIVE') {
                                                    $activeCampaigns++;
                                                }
                                            }
                                            
                                            Notification::make()
                                                ->title('Conexión Exitosa')
                                                ->body("Se encontraron {$activeCampaigns} campañas activas en la cuenta publicitaria {$state}")
                                                ->success()
                                                ->send();
                                                
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error de Conexión')
                                                ->body('Error conectando con Facebook: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),
                        
                        Forms\Components\Select::make('selected_page_id')
                            ->label('Fan Page (Opcional)')
                            ->helperText('Selecciona una fan page específica para filtrar campañas. Deja vacío para todas las páginas.')
                            ->placeholder('Selecciona una fan page')
                            ->searchable()
                            ->options(function ($get, $record) {
                                $appId = $get('app_id');
                                $appSecret = $get('app_secret');
                                $accessToken = $get('access_token');
                                
                                // Verificar que tengamos todos los datos necesarios
                                if (!$appId || !$appSecret || !$accessToken) {
                                    return [];
                                }
                                
                                try {
                                    // Usar API de Graph directamente para evitar problemas con appsecret_proof
                                    $url = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$accessToken}";
                                    $response = file_get_contents($url);
                                    $data = json_decode($response, true);
                                    
                                    $options = [];
                                    if (isset($data['data'])) {
                                        foreach ($data['data'] as $page) {
                                            $options[$page['id']] = $page['name'] . ' (' . $page['category'] . ')';
                                        }
                                    }
                                    
                                    return $options;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Error obteniendo páginas: ' . $e->getMessage());
                                    return ['error' => 'Error conectando con Facebook: ' . $e->getMessage()];
                                }
                            })
                            ->visible(fn ($get) => !empty($get('selected_ad_account_id')))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar campañas y anuncios cuando cambie la página
                                $set('selected_campaign_ids', []);
                                $set('selected_ad_ids', []);
                            })
                            ->suffixAction(
                                Action::make('refresh_pages')
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
                                                ->body('Completa App ID, App Secret y Access Token antes de refrescar las páginas.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            // Usar API de Graph directamente
                                            $url = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$accessToken}";
                                            $response = file_get_contents($url);
                                            $data = json_decode($response, true);
                                            
                                            $pagesCount = 0;
                                            if (isset($data['data'])) {
                                                $pagesCount = count($data['data']);
                                            }
                                            
                                            Notification::make()
                                                ->title('Páginas Actualizadas')
                                                ->body("Se encontraron {$pagesCount} páginas en tu cuenta de Facebook")
                                                ->success()
                                                ->send();
                                                
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Error obteniendo páginas: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),
                        Forms\Components\Select::make('selected_campaign_ids')
                            ->label('Campañas Específicas (Opcional)')
                            ->helperText('Si seleccionas campañas específicas, solo se sincronizarán los anuncios de esas campañas. Deja vacío para todas las campañas.')
                            ->placeholder('Selecciona campañas')
                            ->multiple()
                            ->searchable()
                            ->options(function ($get, $record) {
                                $adAccountId = $get('selected_ad_account_id');
                                $pageId = $get('selected_page_id');
                                $appId = $get('app_id');
                                $appSecret = $get('app_secret');
                                $accessToken = $get('access_token');
                                
                                // Verificar que tengamos todos los datos necesarios
                                if (!$adAccountId || !$appId || !$appSecret || !$accessToken) {
                                    return [];
                                }
                                
                                try {
                                    // Inicializar Facebook API con datos del formulario
                                    Api::init($appId, $appSecret, $accessToken);
                                    
                                    $account = new AdAccount('act_' . $adAccountId);
                                    
                                    // Si hay una página seleccionada, filtrar campañas por esa página
                                    $params = ['id', 'name', 'status'];
                                    if ($pageId) {
                                        $params[] = 'page_id';
                                    }
                                    
                                    $campaigns = $account->getCampaigns($params);
                                    
                                    $options = [];
                                    foreach ($campaigns as $campaign) {
                                        if ($campaign->status == 'ACTIVE') {
                                            // Si hay página seleccionada, solo mostrar campañas de esa página
                                            if ($pageId && isset($campaign->page_id) && $campaign->page_id != $pageId) {
                                                continue;
                                            }
                                            $options[$campaign->id] = $campaign->name . ' (ID: ' . $campaign->id . ')';
                                        }
                                    }
                                    
                                    return $options;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Error obteniendo campañas: ' . $e->getMessage());
                                    return ['error' => 'Error conectando con Facebook: ' . $e->getMessage()];
                                }
                            })
                            ->visible(fn ($get) => !empty($get('selected_ad_account_id')))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar anuncios seleccionados cuando cambien las campañas
                                $set('selected_ad_ids', []);
                            })
                            ->suffixAction(
                                Action::make('refresh_campaigns')
                                    ->label('Refrescar')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('info')
                                    ->action(function ($state, $set, $get) {
                                        $adAccountId = $get('selected_ad_account_id');
                                        $appId = $get('app_id');
                                        $appSecret = $get('app_secret');
                                        $accessToken = $get('access_token');
                                        
                                        if (!$adAccountId || !$appId || !$appSecret || !$accessToken) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Completa todos los campos antes de refrescar las campañas.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            Api::init($appId, $appSecret, $accessToken);
                                            $account = new AdAccount('act_' . $adAccountId);
                                            $campaigns = $account->getCampaigns(['id', 'name', 'status'], ['limit' => 250]);
                                            
                                            $activeCampaigns = 0;
                                            foreach ($campaigns as $campaign) {
                                                if ($campaign->status == 'ACTIVE') {
                                                    $activeCampaigns++;
                                                }
                                            }
                                            
                                            Notification::make()
                                                ->title('Campañas Actualizadas')
                                                ->body("Se encontraron {$activeCampaigns} campañas activas")
                                                ->success()
                                                ->send();
                                                
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Error obteniendo campañas: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),
                        
                        Forms\Components\Select::make('selected_ad_ids')
                            ->label('Anuncios Específicos (Opcional)')
                            ->helperText('Selecciona anuncios específicos de las campañas elegidas. Deja vacío para todos los anuncios.')
                            ->placeholder('Selecciona anuncios')
                            ->multiple()
                            ->searchable()
                            ->options(function ($get, $record) {
                                $adAccountId = $get('selected_ad_account_id');
                                $campaignIds = $get('selected_campaign_ids');
                                $pageId = $get('selected_page_id');
                                $appId = $get('app_id');
                                $appSecret = $get('app_secret');
                                $accessToken = $get('access_token');
                                
                                // Verificar que tengamos todos los datos necesarios
                                if (!$adAccountId || !$campaignIds || !$appId || !$appSecret || !$accessToken) {
                                    return [];
                                }
                                
                                try {
                                    // Usar API de Graph directamente para obtener anuncios y filtrar por página
                                    $baseUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads";
                                    $fields = 'id,name,campaign_id,creative';
                                    $params = [
                                        'fields' => $fields,
                                        'limit' => 250,
                                        'access_token' => $accessToken
                                    ];
                                    
                                    $url = $baseUrl . '?' . http_build_query($params);
                                    $response = file_get_contents($url);
                                    $adsData = json_decode($response, true);
                                    
                                    $options = [];
                                    if (isset($adsData['data'])) {
                                        foreach ($adsData['data'] as $ad) {
                                            // Verificar que el anuncio pertenezca a las campañas seleccionadas
                                            if (!in_array($ad['campaign_id'], $campaignIds)) {
                                                continue;
                                            }
                                            
                                            // Si hay página seleccionada, verificar que el anuncio pertenezca a esa página
                                            if ($pageId && isset($ad['creative']['id'])) {
                                                $creativeId = $ad['creative']['id'];
                                                $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$accessToken}";
                                                $creativeResponse = file_get_contents($creativeUrl);
                                                $creativeData = json_decode($creativeResponse, true);
                                                
                                                // Verificar si el creativo pertenece a la página seleccionada
                                                if (isset($creativeData['object_story_spec']['page_id']) && 
                                                    $creativeData['object_story_spec']['page_id'] != $pageId) {
                                                    continue;
                                                }
                                            }
                                            
                                            $options[$ad['id']] = $ad['name'] . ' (ID: ' . $ad['id'] . ')';
                                        }
                                    }
                                    
                                    return $options;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Error obteniendo anuncios: ' . $e->getMessage());
                                    return ['error' => 'Error conectando con Facebook: ' . $e->getMessage()];
                                }
                            })
                            ->visible(fn ($get) => !empty($get('selected_ad_account_id')) && !empty($get('selected_campaign_ids')))
                            ->reactive()
                            ->suffixAction(
                                Action::make('refresh_ads')
                                    ->label('Refrescar')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('info')
                                    ->action(function ($state, $set, $get) {
                                        $adAccountId = $get('selected_ad_account_id');
                                        $campaignIds = $get('selected_campaign_ids');
                                        $appId = $get('app_id');
                                        $appSecret = $get('app_secret');
                                        $accessToken = $get('access_token');
                                        
                                        if (!$adAccountId || !$campaignIds || !$appId || !$appSecret || !$accessToken) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Completa todos los campos antes de refrescar los anuncios.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            // Usar API de Graph directamente para obtener anuncios y filtrar por página
                                            $pageId = $get('selected_page_id');
                                            $baseUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads";
                                            $fields = 'id,name,campaign_id,creative';
                                            $params = [
                                                'fields' => $fields,
                                                'limit' => 250,
                                                'access_token' => $accessToken
                                            ];
                                            
                                            $url = $baseUrl . '?' . http_build_query($params);
                                            $response = file_get_contents($url);
                                            $adsData = json_decode($response, true);
                                            
                                            $adsCount = 0;
                                            $filteredAdsCount = 0;
                                            
                                            if (isset($adsData['data'])) {
                                                foreach ($adsData['data'] as $ad) {
                                                    $adsCount++;
                                                    
                                                    // Verificar que el anuncio pertenezca a las campañas seleccionadas
                                                    if (!in_array($ad['campaign_id'], $campaignIds)) {
                                                        continue;
                                                    }
                                                    
                                                    // Si hay página seleccionada, verificar que el anuncio pertenezca a esa página
                                                    if ($pageId && isset($ad['creative']['id'])) {
                                                        $creativeId = $ad['creative']['id'];
                                                        $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$accessToken}";
                                                        $creativeResponse = file_get_contents($creativeUrl);
                                                        $creativeData = json_decode($creativeResponse, true);
                                                        
                                                        // Verificar si el creativo pertenece a la página seleccionada
                                                        if (isset($creativeData['object_story_spec']['page_id']) && 
                                                            $creativeData['object_story_spec']['page_id'] != $pageId) {
                                                            continue;
                                                        }
                                                    }
                                                    
                                                    $filteredAdsCount++;
                                                }
                                            }
                                            
                                            $message = $pageId 
                                                ? "Se encontraron {$filteredAdsCount} anuncios filtrados por página de {$adsCount} totales"
                                                : "Se encontraron {$filteredAdsCount} anuncios en las campañas seleccionadas";
                                            
                                            Notification::make()
                                                ->title('Anuncios Actualizados')
                                                ->body($message)
                                                ->success()
                                                ->send();
                                                
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Error obteniendo anuncios: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),
                    ])->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true)
                            ->helperText('Activa o desactiva esta cuenta'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Nombre de la Cuenta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('account_id')
                    ->label('ID de Cuenta')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copiado al portapapeles'),
                IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                TableAction::make('view_campaigns')
                    ->label('Ver Campañas')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->action(function (FacebookAccountResource $record) {
                        // Redirigir a la página de campañas
                        return redirect()->route('filament.admin.resources.facebook-accounts.campaigns', $record);
                    })
                    ->url(fn (FacebookAccount $record) => route('filament.admin.resources.facebook-accounts.campaigns', $record)),
                    
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
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
            'index' => Pages\ListFacebookAccounts::route('/'),
            'create' => Pages\CreateFacebookAccount::route('/create'),
            'edit' => Pages\EditFacebookAccount::route('/{record}/edit'),
            'campaigns' => Pages\ViewCampaigns::route('/{record}/campaigns'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
