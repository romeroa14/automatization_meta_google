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
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class ActiveCampaignsResource extends Resource
{
    protected static ?string $model = ActiveCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Campañas Activas';

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    protected static ?int $navigationSort = 1;

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
                        // Los valores ya están convertidos a dólares en la BD
                        $dailyBudget = $record->campaign_daily_budget ?? $record->adset_daily_budget;
                        return $dailyBudget ?? 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                    
                TextColumn::make('amount_spent')
                    ->label('Gastado')
                    ->getStateUsing(function ($record) {
                        // Si hay override por rango, usarlo
                        $override = $record->campaign_data['amount_spent_override'] ?? null;
                        if ($override !== null) {
                            return (float) $override;
                        }
                        
                        // Usar el valor de la base de datos (ya convertido a dólares)
                        return $record->amount_spent ?? 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('warning')
                    ->summarize(Sum::make('amount_spent')),
                    
                TextColumn::make('campaign_total_budget')
                    ->label('Presupuesto Restante')
                    ->getStateUsing(function ($record) {
                        // Usar el nuevo método que maneja todos los casos
                        $remainingBudget = $record->getCampaignRemainingBudgetFromAdsets();
                        
                        if ($remainingBudget !== null) {
                            return $remainingBudget;
                        }
                        
                        // Fallback a la lógica anterior si no funciona
                        $dailyBudget = $record->campaign_daily_budget ?? $record->adset_daily_budget;
                        $duration = $record->getCampaignDurationDays() ?? $record->getAdsetDurationDays();
                        
                        if ($dailyBudget && $duration) {
                            $totalBudget = $dailyBudget * $duration;
                            
                            // Usar override si existe
                            $override = $record->campaign_data['amount_spent_override'] ?? null;
                            if ($override !== null) {
                                return max(0, $totalBudget - (float) $override);
                            }

                            // Obtener gastado
                            $spent = $record->amount_spent ?? $record->getAmountSpentFromMeta();
                            
                            if ($spent !== null) {
                                return max(0, $totalBudget - $spent);
                            }
                            
                            return $totalBudget;
                        }
                        
                        return 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success')
                    ->summarize(Sum::make('campaign_total_budget')),
                    
                TextColumn::make('campaign_lifetime_budget')
                    ->label('Presupuesto Total')
                    ->getStateUsing(function ($record) {
                        // Usar la nueva lógica de análisis de AdSets
                        $totalBudgetFromAdsets = $record->getCampaignTotalBudgetFromAdsets();
                        
                        if ($totalBudgetFromAdsets !== null) {
                            return $totalBudgetFromAdsets;
                        }
                        
                        // Fallback a la lógica anterior
                        $dailyBudget = $record->campaign_daily_budget ?? $record->adset_daily_budget;
                        $duration = $record->getCampaignDurationDays() ?? $record->getAdsetDurationDays();
                        
                        if ($dailyBudget && $duration) {
                            return $dailyBudget * $duration;
                        }
                        
                        return 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('info'),
                    
                TextColumn::make('campaign_duration_days')
                    ->label('Duración')
                    ->getStateUsing(function ($record) {
                        // Usar la nueva lógica de análisis de AdSets
                        $durationFromAdsets = $record->getCampaignDurationFromAdsets();
                        
                        if ($durationFromAdsets !== null) {
                            return $durationFromAdsets;
                        }
                        
                        // Fallback a la lógica anterior
                        return $record->getCampaignDurationDays();
                    })
                    ->suffix(' días')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('adsets_count')
                    ->label('AdSets')
                    ->getStateUsing(function ($record) {
                        return $record->adsets_count ?? $record->getAdsetsCount();
                    })
                    ->badge()
                    ->color('info'),
                    
                TextColumn::make('ads_count')
                    ->label('Anuncios')
                    ->getStateUsing(function ($record) {
                        return $record->ads_count ?? $record->getAdsCount();
                    })
                    ->badge()
                    ->color('success'),
                    
                TextColumn::make('date_range')
                    ->label('Rango de Fechas')
                    ->getStateUsing(function ($record) {
                        $range = $record->campaign_data['amount_spent_range'] ?? null;
                        if ($range && isset($range['since']) && isset($range['until'])) {
                            $since = \Carbon\Carbon::parse($range['since'])->format('d/m/Y');
                            $until = \Carbon\Carbon::parse($range['until'])->format('d/m/Y');
                            return "{$since} - {$until}";
                        }
                        return 'N/A';
                    })
                    ->badge()
                    ->color('warning')
                    ->sortable(false)
                    ->tooltip('Rango de fechas usado para calcular el gasto')
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
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        // Filtrar por estado real calculado (PostgreSQL compatible)
                        return $query->whereRaw("
                            CASE 
                                WHEN campaign_data->>'status' = 'ACTIVE' 
                                    AND campaign_data->>'start_time' IS NOT NULL 
                                    AND campaign_data->>'stop_time' IS NOT NULL
                                    AND NOW() BETWEEN (campaign_data->>'start_time')::timestamp AND (campaign_data->>'stop_time')::timestamp
                                THEN 'ACTIVE'
                                WHEN campaign_data->>'start_time' IS NOT NULL 
                                    AND NOW() < (campaign_data->>'start_time')::timestamp
                                THEN 'SCHEDULED'
                                WHEN campaign_data->>'stop_time' IS NOT NULL 
                                    AND NOW() > (campaign_data->>'stop_time')::timestamp
                                THEN 'COMPLETED'
                                ELSE campaign_data->>'status'
                            END = ?
                        ", [$data['value']]);
                    }),
                    
                Tables\Filters\SelectFilter::make('ad_account_id')
                    ->label('Cuenta Publicitaria')
                    ->options(function () {
                        return \App\Models\ActiveCampaign::select('ad_account_id')
                            ->distinct()
                            ->pluck('ad_account_id', 'ad_account_id')
                            ->mapWithKeys(function ($id) {
                                return [$id => 'ID: ' . $id];
                            });
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return $query->where('ad_account_id', $data['value']);
                    }),
                    
                Tables\Filters\TernaryFilter::make('has_date_range')
                    ->label('Con Rango de Fechas')
                    ->placeholder('Todas las campañas')
                    ->trueLabel('Solo con rango')
                    ->falseLabel('Sin rango')
                    ->queries(
                        true: fn ($query) => $query->whereRaw("campaign_data->>'amount_spent_range' IS NOT NULL"),
                        false: fn ($query) => $query->whereRaw("campaign_data->>'amount_spent_range' IS NULL")
                    ),
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
                    
                Action::make('view_adsets')
                    ->label('Ver AdSets')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('warning')
                    ->modalHeading('AdSets de la Campaña')
                    ->modalContent(fn ($record) => view('components.raw-html', [
                        'html' => self::getAdsetsDetailsHtml($record)
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                    
                Action::make('delete_campaign_complete')
                    ->label('Eliminar Campaña Completa')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Campaña Completa')
                    ->modalDescription(fn($record) => 'Esta acción eliminará TODA la campaña "' . $record->meta_campaign_name . '" incluyendo todos sus AdSets y Anuncios. Esta acción no se puede deshacer.')
                    ->action(function ($record) {
                        $campaignId = $record->meta_campaign_id;
                        $campaignName = $record->meta_campaign_name;
                        
                        // Contar registros antes de eliminar
                        $totalRecords = \App\Models\ActiveCampaign::where('meta_campaign_id', $campaignId)->count();
                        
                        // Eliminar todos los registros de la campaña
                        \App\Models\ActiveCampaign::where('meta_campaign_id', $campaignId)->delete();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Campaña Eliminada Completamente')
                            ->body("Se eliminó la campaña '{$campaignName}' con {$totalRecords} registros (AdSets y Anuncios).")
                            ->success()
                            ->send();
                    }),
                    
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados')
                        ->action(function ($records) {
                            // Eliminar todos los registros de las campañas seleccionadas
                            $campaignIds = $records->pluck('meta_campaign_id')->unique();
                            
                            foreach ($campaignIds as $campaignId) {
                                \App\Models\ActiveCampaign::where('meta_campaign_id', $campaignId)->delete();
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Campañas Eliminadas')
                                ->body('Se eliminaron ' . $campaignIds->count() . ' campañas completas con todos sus anuncios.')
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('delete_campaign_complete')
                        ->label('Eliminar Campaña Completa')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar Campaña Completa')
                        ->modalDescription('Esta acción eliminará TODA la campaña incluyendo todos sus AdSets y Anuncios. Esta acción no se puede deshacer.')
                        ->action(function ($records) {
                            $campaignIds = $records->pluck('meta_campaign_id')->unique();
                            $totalDeleted = 0;
                            
                            foreach ($campaignIds as $campaignId) {
                                $deleted = \App\Models\ActiveCampaign::where('meta_campaign_id', $campaignId)->delete();
                                $totalDeleted += $deleted;
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Campañas Completas Eliminadas')
                                ->body("Se eliminaron {$campaignIds->count()} campañas completas con {$totalDeleted} registros totales.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // Agrupar por campaña para evitar duplicados (usando MIN para ID y array_agg para JSON)
                return $query->selectRaw('
                    MIN(id) as id,
                    meta_campaign_id,
                    meta_campaign_name,
                    campaign_daily_budget,
                    campaign_total_budget,
                    amount_spent,
                    campaign_status,
                    campaign_objective,
                    facebook_account_id,
                    ad_account_id,
                    campaign_start_time,
                    campaign_stop_time,
                    campaign_created_time,
                    MAX(created_at) as created_at,
                    COUNT(DISTINCT meta_adset_id) as adsets_count,
                    COUNT(DISTINCT meta_ad_id) as ads_count,
                    (array_agg(campaign_data))[1] as campaign_data,
                    (array_agg(adset_data))[1] as adset_data,
                    (array_agg(ad_data))[1] as ad_data
                ')
                ->groupBy([
                    'meta_campaign_id',
                    'meta_campaign_name', 
                    'campaign_daily_budget',
                    'campaign_total_budget',
                    'amount_spent',
                    'campaign_status',
                    'campaign_objective',
                    'facebook_account_id',
                    'ad_account_id',
                    'campaign_start_time',
                    'campaign_stop_time',
                    'campaign_created_time'
                ]);
            });
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
    
    /**
     * Generar HTML para detalles de AdSets
     */
    private static function getAdsetsDetailsHtml($record)
    {
        $adsets = $record->getAdsets();
        
        $html = '<div class="space-y-4">';
        $html .= '<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">';
        $html .= '<h3 class="text-lg font-bold text-yellow-800 mb-3">📊 AdSets de la Campaña</h3>';
        $html .= '<p class="text-sm text-yellow-700">Total de AdSets: ' . $adsets->count() . '</p>';
        $html .= '</div>';
        
        foreach ($adsets as $adset) {
            $html .= '<div class="bg-white p-4 rounded-lg border border-gray-200">';
            $html .= '<h4 class="font-bold text-gray-800">' . ($adset->meta_adset_name ?? 'N/A') . '</h4>';
            $html .= '<div class="grid grid-cols-2 gap-2 mt-2 text-sm">';
            $html .= '<div><strong>Estado:</strong> ' . ($adset->adset_status ?? 'N/A') . '</div>';
            $html .= '<div><strong>Presupuesto Diario:</strong> $' . number_format($adset->adset_daily_budget ?? 0, 2) . '</div>';
            $html .= '<div><strong>Presupuesto Total:</strong> $' . number_format($adset->adset_lifetime_budget ?? 0, 2) . '</div>';
            $html .= '<div><strong>Inicio:</strong> ' . ($adset->adset_start_time ? $adset->adset_start_time->format('d/m/Y H:i') : 'N/A') . '</div>';
            $html .= '<div><strong>Fin:</strong> ' . ($adset->adset_stop_time ? $adset->adset_stop_time->format('d/m/Y H:i') : 'N/A') . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
