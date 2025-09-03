<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActiveCampaignsResource\Pages;
use App\Models\ActiveCampaign;
use App\Models\FacebookAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class ActiveCampaignsResource extends Resource
{
    protected static ?string $model = ActiveCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Campañas Activas';

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Selección de Cuenta Publicitaria')
                    ->description('Selecciona la cuenta de Facebook y la cuenta publicitaria para cargar las campañas activas')
                    ->schema([
                        Select::make('facebook_account_id')
                            ->label('Cuenta de Facebook')
                            ->options(FacebookAccount::pluck('account_name', 'id'))
                            ->required()
                            ->searchable()
                            ->placeholder('Selecciona una cuenta de Facebook')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('selected_ad_account_id', null);
                            }),
                            
                        Select::make('selected_ad_account_id')
                            ->label('Cuenta Publicitaria')
                            ->options(function ($get) {
                                // Usar las opciones cargadas dinámicamente
                                $accountOptions = $get('account_options') ?? [];
                                return $accountOptions;
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Selecciona una cuenta publicitaria')
                            ->disabled(fn ($get) => !$get('facebook_account_id'))
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('refresh_ad_accounts')
                                    ->label('Refrescar')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('info')
                                    ->action(function ($state, $set, $get) {
                                        $facebookAccountId = $get('facebook_account_id');
                                        
                                        if (!$facebookAccountId) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Error')
                                                ->body('Selecciona una cuenta de Facebook primero.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        $facebookAccount = FacebookAccount::find($facebookAccountId);
                                        if (!$facebookAccount || !$facebookAccount->access_token) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Error')
                                                ->body('La cuenta seleccionada no tiene token de acceso configurado.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Cargando cuentas...')
                                                ->body('Obteniendo cuentas publicitarias de Facebook. Esto puede tomar unos segundos.')
                                                ->info()
                                                ->send();
                                            
                                            $url = "https://graph.facebook.com/v18.0/me/adaccounts?limit=250&access_token={$facebookAccount->access_token}";
                                            $response = file_get_contents($url);
                                            $data = json_decode($response, true);
                                            
                                            if (!isset($data['data'])) {
                                                \Filament\Notifications\Notification::make()
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
                                            
                                            \Filament\Notifications\Notification::make()
                                                ->title('Cuentas Actualizadas')
                                                ->body("Se encontraron " . count($accountOptions) . " cuentas publicitarias en tu cuenta de Facebook")
                                                ->success()
                                                ->send();
                                                
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Error')
                                                ->body('Error obteniendo cuentas: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),
                            
                        // Campo oculto para almacenar las opciones
                        Hidden::make('account_options'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Niveles jerárquicos
                TextColumn::make('meta_campaign_id')
                    ->label('ID Campaña')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('meta_campaign_name')
                    ->label('Nombre Campaña')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                TextColumn::make('meta_adset_id')
                    ->label('ID AdSet')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('meta_adset_name')
                    ->label('Nombre AdSet')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                    
                TextColumn::make('meta_ad_id')
                    ->label('ID Anuncio')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('meta_ad_name')
                    ->label('Nombre Anuncio')
                    ->searchable()
                    ->sortable()
                    ->limit(25),
                
                // Presupuestos de campaña
                TextColumn::make('campaign_daily_budget')
                    ->label('Presupuesto Diario Campaña')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('campaign_total_budget')
                    ->label('Presupuesto Total Campaña')
                    ->money('USD')
                    ->sortable(),
                    
                // Presupuestos de adset
                TextColumn::make('adset_daily_budget')
                    ->label('Presupuesto Diario AdSet')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('adset_lifetime_budget')
                    ->label('Presupuesto Total AdSet')
                    ->money('USD')
                    ->sortable(),
                
                // Fechas
                TextColumn::make('campaign_start_time')
                    ->label('Inicio Campaña')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('campaign_stop_time')
                    ->label('Fin Campaña')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('adset_start_time')
                    ->label('Inicio AdSet')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                TextColumn::make('adset_stop_time')
                    ->label('Fin AdSet')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                // Estados
                BadgeColumn::make('campaign_status')
                    ->label('Estado Campaña')
                    ->colors([
                        'success' => 'ACTIVE',
                        'danger' => 'PAUSED',
                        'warning' => 'DELETED',
                    ]),
                    
                BadgeColumn::make('adset_status')
                    ->label('Estado AdSet')
                    ->colors([
                        'success' => 'ACTIVE',
                        'danger' => 'PAUSED',
                        'warning' => 'DELETED',
                    ]),
                    
                BadgeColumn::make('ad_status')
                    ->label('Estado Anuncio')
                    ->colors([
                        'success' => 'ACTIVE',
                        'danger' => 'PAUSED',
                        'warning' => 'DELETED',
                    ]),
                    
                // Objetivo
                TextColumn::make('campaign_objective')
                    ->label('Objetivo')
                    ->searchable()
                    ->sortable(),
                    
                // Métricas calculadas
                TextColumn::make('campaign_duration')
                    ->label('Duración Campaña')
                    ->getStateUsing(fn ($record) => $record->getCampaignDurationDays())
                    ->suffix(' días')
                    ->sortable(),
                    
                TextColumn::make('adset_duration')
                    ->label('Duración AdSet')
                    ->getStateUsing(fn ($record) => $record->getAdsetDurationDays())
                    ->suffix(' días')
                    ->sortable(),
                    
                TextColumn::make('campaign_remaining_budget')
                    ->label('Presupuesto Restante Campaña')
                    ->getStateUsing(fn ($record) => $record->getCampaignRemainingBudget())
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('adset_remaining_budget')
                    ->label('Presupuesto Restante AdSet')
                    ->getStateUsing(fn ($record) => $record->getAdsetRemainingBudget())
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detalles Completos de la Campaña')
                    ->modalContent(fn ($record) => view('components.raw-html', [
                        'html' => '<h3>Datos de Campaña</h3><pre>' . json_encode($record->campaign_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>' .
                                 '<h3>Datos de AdSet</h3><pre>' . json_encode($record->adset_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>' .
                                 '<h3>Datos de Anuncio</h3><pre>' . json_encode($record->ad_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>'
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                    
                Action::make('refresh_campaigns')
                    ->label('Refrescar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function ($record) {
                        // Aquí se podría implementar la lógica de refresh individual
                        Notification::make()
                            ->title('Campaña refrescada')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::getModel()::query();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActiveCampaigns::route('/'),
        ];
    }
}
