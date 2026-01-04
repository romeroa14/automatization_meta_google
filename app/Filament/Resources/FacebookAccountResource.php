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

    protected static ?string $navigationGroup = 'Automatizaciones';

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

                        Forms\Components\TextInput::make('app_id')
                            ->label('App ID')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('123456789012345')
                            ->live(),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->required()
                            ->password()
                            ->maxLength(255)
                            ->live()
                            ->visible(fn ($get) => !empty($get('app_id'))),
                        Forms\Components\Textarea::make('access_token')
                            ->label('Access Token')
                            ->required()
                            ->rows(3)
                            ->placeholder('EAA...')
                            ->live()
                            ->visible(fn ($get) => !empty($get('app_id')) && !empty($get('app_secret')))
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar campaña seleccionada cuando cambie el token
                                $set('selected_campaign_id', null);
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración de Automatización')
                    ->description('Configura qué datos se sincronizarán')
                    ->schema([
                        Forms\Components\Select::make('selected_ad_account_id')
                            ->label('Cuenta Publicitaria')
                            ->helperText('Selecciona la cuenta publicitaria que quieres usar.')
                            ->placeholder('Selecciona una cuenta publicitaria')
                            ->searchable()
                            // ->required()
                            ->live()
                            ->options(function ($get) {
                                // Mostrar opciones almacenadas temporalmente en el estado
                                $accountOptions = $get('account_options') ?? [];
                                return $accountOptions;
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar página, campañas y anuncios cuando cambie la cuenta
                                $set('selected_page_id', null);
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
                                            // Mostrar notificación de carga
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
                                            
                                            // Actualizar las opciones del campo
                                            $set('account_options', $accountOptions);
                                            $set('selected_ad_account_id', null);
                                            $set('page_options', []);
                                            $set('selected_page_id', null);
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

                        Forms\Components\Select::make('selected_page_id')
                            ->label('Fan Page')
                            ->helperText('Selecciona una fan page de la cuenta publicitaria seleccionada.')
                            ->placeholder('Selecciona una fan page')
                            ->searchable()
                            // ->required()
                            ->live()
                            ->visible(fn ($get) => !empty($get('selected_ad_account_id')))
                            ->options(function ($get) {
                                // Mostrar opciones almacenadas temporalmente en el estado
                                $pageOptions = $get('page_options') ?? [];
                                return $pageOptions;
                            })
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
                                            // Mostrar notificación de carga
                                            Notification::make()
                                                ->title('Cargando páginas...')
                                                ->body('Obteniendo páginas de Facebook. Esto puede tomar unos segundos.')
                                                ->info()
                                                ->send();
                                            
                                            $url = "https://graph.facebook.com/v18.0/me/accounts?type=page&limit=250&access_token={$accessToken}";
                                            $response = file_get_contents($url);
                                            $data = json_decode($response, true);
                                            
                                            if (!isset($data['data'])) {
                                                Notification::make()
                                                    ->title('Error')
                                                    ->body('No se pudieron obtener las páginas')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                            
                                            $pageOptions = [];
                                            foreach ($data['data'] as $page) {
                                                $pageOptions[$page['id']] = $page['name'] . ' (' . $page['category'] . ')';
                                            }
                                            
                                            // Actualizar las opciones del campo
                                            $set('page_options', $pageOptions);
                                            $set('selected_page_id', null);
                                            $set('campaign_options', []);
                                            $set('selected_campaign_ids', []);
                                            $set('ad_options', []);
                                            $set('selected_ad_ids', []);
                                            
                                            Notification::make()
                                                ->title('Páginas Actualizadas')
                                                ->body("Se encontraron " . count($pageOptions) . " páginas en tu cuenta de Facebook")
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
                            ->label('Campañas de la Fan Page')
                            ->helperText('Selecciona las campañas específicas de la fan page elegida.')
                            ->placeholder('Selecciona campañas')
                            ->multiple()
                            ->searchable()
                            ->options(function ($get, $record) {
                                // Mostrar opciones almacenadas temporalmente en el estado
                                $campaignOptions = $get('campaign_options') ?? [];
                                return $campaignOptions;
                            })
                            ->visible(fn ($get) => !empty($get('selected_page_id')))
                            ->live()
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
                                        $pageId = $get('selected_page_id');
                                        $appId = $get('app_id');
                                        $appSecret = $get('app_secret');
                                        $accessToken = $get('access_token');
                                        
                                        if (!$pageId || !$appId || !$appSecret || !$accessToken) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Selecciona una fan page antes de refrescar las campañas.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            // Usar la cuenta publicitaria seleccionada
                                            $adAccountId = $get('selected_ad_account_id');
                                            if (!$adAccountId) {
                                                Notification::make()
                                                    ->title('Error')
                                                    ->body('Selecciona una cuenta publicitaria antes de refrescar las campañas.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                            
                                            // Mostrar notificación de carga
                                            Notification::make()
                                                ->title('Cargando campañas...')
                                                ->body('Obteniendo y filtrando campañas por página. Esto puede tomar unos segundos.')
                                                ->info()
                                                ->send();
                                            
                                            // Obtener todas las campañas de la cuenta publicitaria
                                            $campaignsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/campaigns?fields=id,name,status&limit=250&access_token={$accessToken}";
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
                                            
                                            // Obtener anuncios y filtrar por página
                                            $adsUrl = "https://graph.facebook.com/v18.0/act_{$adAccountId}/ads?fields=id,name,campaign_id,creative&limit=250&access_token={$accessToken}";
                                            $adsResponse = file_get_contents($adsUrl);
                                            $adsData = json_decode($adsResponse, true);
                                            
                                            $campaignsForPage = [];
                                            $processedAds = 0;
                                            if (isset($adsData['data'])) {
                                                foreach ($adsData['data'] as $ad) {
                                                    $processedAds++;
                                                    if (isset($ad['creative']['id'])) {
                                                        $creativeId = $ad['creative']['id'];
                                                        $creativeUrl = "https://graph.facebook.com/v18.0/{$creativeId}?fields=object_story_spec&access_token={$accessToken}";
                                                        $creativeResponse = @file_get_contents($creativeUrl);
                                                        if ($creativeResponse !== false) {
                                                            $creativeData = json_decode($creativeResponse, true);
                                                            
                                                            if (isset($creativeData['object_story_spec']['page_id']) && 
                                                                $creativeData['object_story_spec']['page_id'] == $pageId) {
                                                                $campaignsForPage[$ad['campaign_id']] = true;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            $filteredCampaigns = [];
                                            foreach ($campaignsData['data'] as $campaign) {
                                                if ($campaign['status'] == 'ACTIVE' && isset($campaignsForPage[$campaign['id']])) {
                                                    $filteredCampaigns[] = $campaign;
                                                }
                                            }
                                            
                                            // Guardar las campañas filtradas en el estado del formulario
                                            $campaignOptions = [];
                                            foreach ($filteredCampaigns as $campaign) {
                                                $campaignOptions[$campaign['id']] = $campaign['name'] . ' (ID: ' . $campaign['id'] . ')';
                                            }
                                            
                                            // Actualizar las opciones del campo
                                            $set('campaign_options', $campaignOptions);
                                            $set('selected_campaign_ids', []);
                                            
                                            Notification::make()
                                                ->title('Campañas Actualizadas')
                                                ->body("Se encontraron " . count($filteredCampaigns) . " campañas de la fan page seleccionada. Procesados {$processedAds} anuncios.")
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
                        
                        Forms\Components\Hidden::make('account_options'),
                        Forms\Components\Hidden::make('page_options'),
                        Forms\Components\Hidden::make('campaign_options'),
                        Forms\Components\Hidden::make('ad_options'),
                        
                        Forms\Components\Select::make('selected_ad_ids')
                            ->label('Anuncios Específicos')
                            ->helperText('Selecciona anuncios específicos de las campañas elegidas. Deja vacío para todos los anuncios.')
                            ->placeholder('Selecciona anuncios')
                            ->multiple()
                            ->searchable()
                            ->options(function ($get, $record) {
                                // Mostrar opciones almacenadas temporalmente en el estado
                                $adOptions = $get('ad_options') ?? [];
                                return $adOptions;
                            })
                            ->visible(fn ($get) => !empty($get('selected_ad_account_id')) && !empty($get('selected_campaign_ids')))
                            ->live()
                            ->suffixAction(
                                Action::make('refresh_ads')
                                    ->label('Refrescar')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('info')
                                    ->action(function ($state, $set, $get) {
                                        $adAccountId = $get('selected_ad_account_id');
                                        $campaignIds = $get('selected_campaign_ids');
                                        $pageId = $get('selected_page_id');
                                        $appId = $get('app_id');
                                        $appSecret = $get('app_secret');
                                        $accessToken = $get('access_token');
                                        
                                        if (!$adAccountId || !$campaignIds || !$appId || !$appSecret || !$accessToken) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Selecciona campañas antes de refrescar los anuncios.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            // Mostrar notificación de carga
                                            Notification::make()
                                                ->title('Cargando anuncios...')
                                                ->body('Obteniendo anuncios de las campañas seleccionadas. Esto puede tomar unos segundos.')
                                                ->info()
                                                ->send();
                                            
                                            // Obtener anuncios de las campañas seleccionadas
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
                                            
                                            if (!isset($adsData['data'])) {
                                                Notification::make()
                                                    ->title('Error')
                                                    ->body('No se pudieron obtener los anuncios')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                            
                                            $adsCount = 0;
                                            $filteredAds = [];
                                            
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
                                                    $creativeResponse = @file_get_contents($creativeUrl);
                                                    if ($creativeResponse !== false) {
                                                        $creativeData = json_decode($creativeResponse, true);
                                                        
                                                        // Verificar si el creativo pertenece a la página seleccionada
                                                        if (isset($creativeData['object_story_spec']['page_id']) && 
                                                            $creativeData['object_story_spec']['page_id'] != $pageId) {
                                                            continue;
                                                        }
                                                    }
                                                }
                                                
                                                $filteredAds[] = $ad;
                                            }
                                            
                                            // Guardar las opciones de anuncios en el estado
                                            $adOptions = [];
                                            foreach ($filteredAds as $ad) {
                                                $adOptions[$ad['id']] = $ad['name'] . ' (ID: ' . $ad['id'] . ')';
                                            }
                                            
                                            // Actualizar las opciones del campo
                                            $set('ad_options', $adOptions);
                                            $set('selected_ad_ids', []);
                                            
                                            $message = $pageId 
                                                ? "Se encontraron " . count($filteredAds) . " anuncios filtrados por página de {$adsCount} totales"
                                                : "Se encontraron " . count($filteredAds) . " anuncios en las campañas seleccionadas";
                                            
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
                        Forms\Components\Toggle::make('is_oauth_primary')
                            ->label('Cuenta Principal para OAuth')
                            ->default(false)
                            ->helperText('Marcar como cuenta principal para el login de clientes con Facebook. Solo una cuenta debe tener esto activo.')
                            ->afterStateUpdated(function ($state, $record) {
                                // Si se activa, desactivar las demás
                                if ($state && $record) {
                                    \App\Models\FacebookAccount::where('id', '!=', $record->id)
                                        ->update(['is_oauth_primary' => false]);
                                }
                            }),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('selected_page_id')
                    ->label('Fan Page')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copiado al portapapeles'),

                Tables\Columns\TextColumn::make('selected_ad_account_id')
                    ->label('Cuenta Publicitaria')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copiado al portapapeles'),
                
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_oauth_primary')
                    ->label('OAuth Principal')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

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

