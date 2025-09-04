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
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

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
                // Solo lo esencial

                TextColumn::make('campaign_status')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        // Usar el estado real basado en fechas
                        return $record->getRealCampaignStatus();
                    })
                    ->badge()
                    ->colors([
                        'success' => 'ACTIVE',
                        'info' => 'SCHEDULED',
                        'warning' => 'COMPLETED',
                        'danger' => 'PAUSED',
                        'secondary' => 'DELETED',
                        'gray' => 'UNKNOWN',
                    ]),

                TextColumn::make('meta_campaign_name')
                    ->label('Nombre de Campaña')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->weight('bold'),
                    
                TextColumn::make('campaign_daily_budget')
                    ->label('Presupuesto Diario')
                    ->getStateUsing(function ($record) {
                        // Meta API devuelve valores en centavos, dividir entre 100
                        $dailyBudget = $record->campaign_daily_budget ?? $record->adset_daily_budget;
                        return $dailyBudget ? $dailyBudget / 100 : 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                    
                TextColumn::make('campaign_data')
                    ->label('Gastado')
                    ->getStateUsing(function ($record) {
                        // Si hay override por rango, usarlo
                        $override = $record->campaign_data['amount_spent_override'] ?? null;
                        if ($override !== null) {
                            return (float) $override;
                        }
                        // Mostrar valor tal como lo devuelve Meta API
                        $spent = $record->getAmountSpentFromMeta();
                        
                        if ($spent !== null) {
                            return $spent; // Valor original de Meta
                        }
                        
                        // Si no hay datos de Meta, calcular estimado
                        $spentEstimated = $record->getAmountSpentEstimated();
                        return $spentEstimated ?? 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('warning'),
                    
                TextColumn::make('campaign_total_budget')
                    ->label('Presupuesto Restante')
                    ->getStateUsing(function ($record) {
                        // Obtener presupuesto total (diario × duración) convertido de centavos
                        $dailyBudget = $record->campaign_daily_budget ?? $record->adset_daily_budget;
                        $duration = $record->getCampaignDurationDays() ?? $record->getAdsetDurationDays();
                        
                        if ($dailyBudget && $duration) {
                            // Meta API devuelve valores en centavos, dividir entre 100
                            $totalBudget = ($dailyBudget / 100) * $duration;
                            
                            // Usar override si existe
                            $override = $record->campaign_data['amount_spent_override'] ?? null;
                            if ($override !== null) {
                                return max(0, $totalBudget - (float) $override);
                            }

                            // Obtener gastado (ya está en formato correcto)
                            $spent = $record->getAmountSpentFromMeta();
                            
                            if ($spent !== null) {
                                $remaining = $totalBudget - $spent;
                                return max(0, $remaining);
                            }
                            
                            // Si no hay gastado, usar estimado
                            $spentEstimated = $record->getAmountSpentEstimated();
                            if ($spentEstimated) {
                                $remaining = $totalBudget - $spentEstimated;
                                return max(0, $remaining);
                            }
                            
                            return $totalBudget;
                        }
                        
                        // Fallback: intentar obtener de Meta API y convertir de centavos
                        $remaining = $record->getCampaignBudgetRemainingFromMeta() ?? 
                                   $record->getAdsetBudgetRemainingFromMeta();
                        
                        if ($remaining !== null) {
                            return $remaining / 100; // Convertir de centavos
                        }
                        
                        return 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                    
                TextColumn::make('campaign_lifetime_budget')
                    ->label('Presupuesto Total')
                    ->getStateUsing(function ($record) {
                        $dailyBudget = $record->campaign_daily_budget ?? $record->adset_daily_budget;
                        $duration = $record->getCampaignDurationDays() ?? $record->getAdsetDurationDays();
                        
                        if ($dailyBudget && $duration) {
                            // Meta API devuelve valores en centavos, dividir entre 100
                            return ($dailyBudget / 100) * $duration;
                        }
                        
                        return 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('campaign_duration_days')
                    ->label('Duración')
                    ->getStateUsing(fn ($record) => $record->getCampaignDurationDays())
                    ->suffix(' días')
                    ->sortable()
                    ->badge()
                    ->color('info')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_status')
                    ->label('Estado')
                    ->options([
                        'ACTIVE' => 'Activa',
                        'SCHEDULED' => 'Programada',
                        'COMPLETED' => 'Completada',
                        'PAUSED' => 'Pausada',
                        'DELETED' => 'Eliminada',
                        'UNKNOWN' => 'Desconocido',
                    ]),
            ])
            ->actions([
                Action::make('view_json_details')
                    ->label('Ver Detalles JSON')
                    ->icon('heroicon-o-code-bracket')
                    ->color('info')
                    ->modalHeading('Datos Completos de los 3 Niveles')
                    ->modalContent(fn ($record) => view('components.raw-html', [
                        'html' => '<h3 class="text-lg font-bold mb-3 text-blue-600">📊 ESTADO REAL vs META</h3>' .
                                 '<div class="bg-blue-50 p-3 rounded mb-4 border border-blue-200">' .
                                 '<p><strong>Estado Meta:</strong> ' . ($record->campaign_data['status'] ?? 'N/A') . '</p>' .
                                 '<p><strong>Estado Real:</strong> ' . $record->getRealCampaignStatus() . '</p>' .
                                 '<p><strong>Fecha Inicio:</strong> ' . ($record->campaign_data['start_time'] ?? 'N/A') . '</p>' .
                                 '<p><strong>Fecha Fin:</strong> ' . ($record->campaign_data['stop_time'] ?? 'N/A') . '</p>' .
                                 '<p><strong>Fecha Actual:</strong> ' . now()->toISOString() . '</p>' .
                                 '</div>' .
                                 
                                 '<h3 class="text-lg font-bold mb-3 text-red-600">🔍 DEBUG: Información de Presupuestos</h3>' .
                                 '<pre class="bg-red-50 p-3 rounded text-xs overflow-x-auto mb-4 border border-red-200">' . 
                                 json_encode($record->getBudgetDebugInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>' .
                                 
                                 '<h3 class="text-lg font-bold mb-3">📊 Datos de Campaña</h3>' .
                                 '<pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto mb-4">' . 
                                 json_encode($record->campaign_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>' .
                                 
                                 '<h3 class="text-lg font-bold mb-3">📈 Datos de AdSet</h3>' .
                                 '<pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto mb-4">' . 
                                 json_encode($record->adset_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>' .
                                 
                                 '<h3 class="text-lg font-bold mb-3">🎯 Datos de Anuncio</h3>' .
                                 '<pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">' . 
                                 json_encode($record->ad_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>'
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                    
                
                    
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
