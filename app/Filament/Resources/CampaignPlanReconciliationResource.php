<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignPlanReconciliationResource\Pages;
use App\Models\CampaignPlanReconciliation;
use App\Models\ActiveCampaign;
use App\Models\AdvertisingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CampaignPlanReconciliationResource extends Resource
{
    protected static ?string $model = CampaignPlanReconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Conciliaciones Contables';

    protected static ?string $modelLabel = 'Conciliación Contable';

    protected static ?string $pluralModelLabel = 'Conciliaciones Contables';

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de Conciliación')
                    ->schema([
                        Select::make('active_campaign_id')
                            ->label('Campaña Activa')
                            ->options(function () {
                                return ActiveCampaign::all()->mapWithKeys(function ($campaign) {
                                    return [$campaign->id => $campaign->meta_campaign_name . ' (ID: ' . $campaign->meta_campaign_id . ')'];
                                });
                            })
                            ->searchable()
                            ->disabled(),

                        Select::make('advertising_plan_id')
                            ->label('Plan de Publicidad')
                            ->options(AdvertisingPlan::all()->pluck('plan_name', 'id'))
                            ->searchable()
                            ->disabled(),

                        Select::make('reconciliation_status')
                            ->label('Estado de Conciliación')
                            ->options([
                                'pending' => 'Pendiente',
                                'approved' => 'Aprobada',
                                'rejected' => 'Rechazada',
                            ])
                            ->default('pending')
                            ->required(),

                        DateTimePicker::make('reconciliation_date')
                            ->label('Fecha de Conciliación')
                            ->default(now()),

                        DateTimePicker::make('campaign_start_time')
                            ->label('Inicio de Campaña')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($record) {
                                return $record?->activeCampaign?->campaign_start_time;
                            }),

                        DateTimePicker::make('campaign_stop_time')
                            ->label('Fin de Campaña')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function ($record) {
                                return $record?->activeCampaign?->campaign_stop_time;
                            }),
                    ])
                    ->columns(2),

                Section::make('Datos Contables')
                    ->schema([
                        TextInput::make('planned_budget')
                            ->label('Presupuesto Planificado')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $actualSpent = $get('actual_spent');
                                if ($state && $actualSpent) {
                                    $variance = $state - $actualSpent;
                                    $variancePercentage = $state > 0 ? ($variance / $state) * 100 : 0;
                                    $set('variance', $variance);
                                    $set('variance_percentage', $variancePercentage);
                                }
                            }),

                        TextInput::make('actual_spent')
                            ->label('Gasto Real')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $plannedBudget = $get('planned_budget');
                                if ($state && $plannedBudget) {
                                    $variance = $plannedBudget - $state;
                                    $variancePercentage = $plannedBudget > 0 ? ($variance / $plannedBudget) * 100 : 0;
                                    $set('variance', $variance);
                                    $set('variance_percentage', $variancePercentage);
                                }
                            }),

                        TextInput::make('variance')
                            ->label('Restante')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('variance_percentage')
                            ->label('Variación (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Notas Adicionales')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('instagram_client_name')
                    ->label('Cliente (Instagram)')
                    ->getStateUsing(function ($record) {
                        // Leer el nombre de Instagram desde los datos guardados en reconciliation_data
                        $reconciliationData = $record->reconciliation_data ?? [];
                        $instagramName = $reconciliationData['instagram_client_name'] ?? null;
                        
                        // Si no está guardado, usar fallback del nombre de campaña
                        if (!$instagramName) {
                            $campaignName = $record->activeCampaign->meta_campaign_name;
                            // Extraer nombre del cliente del texto de la campaña como fallback
                            if (preg_match('/^([^|]+?)\s*\|\s*/', $campaignName, $matches)) {
                                $instagramName = trim($matches[1]);
                                $instagramName = preg_replace('/\s*-\s*Copia\s*$/i', '', $instagramName);
                            } else {
                                $instagramName = 'Cliente Sin Identificar';
                            }
                        }
                        
                        return $instagramName;
                    })
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->weight('bold')
                    ->wrap()
                    ->color('info')
                    ->tooltip('Nombre de la cuenta de Instagram del cliente'),

                TextColumn::make('activeCampaign.meta_campaign_name')
                    ->label('Campaña')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->weight('normal')
                    ->wrap()
                    ->color('gray')
                    ->tooltip('Nombre de la campaña publicitaria'),

                TextColumn::make('advertisingPlan.plan_name')
                    ->label('Plan de Publicidad')
                    ->tooltip(fn ($record) => $record->advertisingPlan ? $record->advertisingPlan->plan_summary : 'Plan Personalizado - Sin plan asignado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->advertisingPlan ? 'info' : 'warning')
                    ->wrap()
                    ->limit(30)
                    ->formatStateUsing(fn ($record) => $record->advertisingPlan ? $record->advertisingPlan->plan_name : 'Plan Personalizado'),

                TextColumn::make('activeCampaign.campaign_start_time')
                    ->label('Inicio')
                    ->dateTime('d/m')
                    ->sortable()
                    ->color('info')
                    ->tooltip('Fecha de inicio de la campaña publicitaria'),

                TextColumn::make('activeCampaign.campaign_stop_time')
                    ->label('Fin')
                    ->dateTime('d/m')
                    ->sortable()
                    ->color('warning')
                    ->tooltip('Fecha de finalización de la campaña publicitaria'),

               

                TextColumn::make('activeCampaign.campaign_total_budget')
                    ->label('Total')
                    ->getStateUsing(function ($record) {
                        // Los valores ya están convertidos correctamente en ActiveCampaign
                        $totalBudget = $record->activeCampaign->campaign_total_budget;
                        
                        // Si no hay presupuesto total, calcularlo desde el diario
                        if (!$totalBudget) {
                            $dailyBudget = $record->activeCampaign->campaign_daily_budget ?? $record->activeCampaign->adset_daily_budget;
                            $duration = $record->activeCampaign->getCampaignDurationDays() ?? $record->activeCampaign->getAdsetDurationDays();
                            
                            if ($dailyBudget && $duration) {
                                return $dailyBudget * $duration;
                            }
                        }
                        
                        return $totalBudget ?? 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success')
                    ->size('sm'),
                
                TextColumn::make('activeCampaign.campaign_daily_budget')
                    ->label('Diario')
                    ->getStateUsing(function ($record) {
                        // Los valores ya están convertidos correctamente en ActiveCampaign
                        $dailyBudget = $record->activeCampaign->campaign_daily_budget ?? $record->activeCampaign->adset_daily_budget;
                        return $dailyBudget ?? 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success')
                    ->size('sm'),
                
                
                
                

                

                

               


                

                
                

                TextColumn::make('activeCampaign.debug_info')
                    ->label('Debug Presupuestos')
                    ->getStateUsing(function ($record) {
                        $debug = $record->activeCampaign->getBudgetDebugInfo();
                        $dailyRaw = $debug['meta_campaign']['daily_budget'] ?? $debug['meta_adset']['daily_budget'] ?? 'N/A';
                        $dailyConverted = $debug['database']['campaign_daily_budget'] ?? $debug['database']['adset_daily_budget'] ?? 'N/A';
                        $spentRaw = $debug['meta_campaign']['amount_spent'] ?? $debug['meta_adset']['amount_spent'] ?? 'N/A';
                        $spentConverted = $debug['conversions']['amount_spent_converted_new'] ?? 'N/A';
                        
                        return "Diario: {$dailyRaw}→{$dailyConverted} | Gasto: {$spentRaw}→{$spentConverted}";
                    })
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $debug = $record->activeCampaign->getBudgetDebugInfo();
                        return json_encode($debug, JSON_PRETTY_PRINT);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reconciliation_status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                        'paused' => 'Pausada',
                    ]),

                Tables\Filters\SelectFilter::make('advertising_plan_id')
                    ->label('Plan de Publicidad')
                    ->relationship('advertisingPlan', 'plan_name'),

                Tables\Filters\Filter::make('campaign_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio Desde'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Inicio Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('activeCampaign', function ($q) use ($date) {
                                    $q->where('campaign_start_time', '>=', $date);
                                }),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('activeCampaign', function ($q) use ($date) {
                                    $q->where('campaign_start_time', '<=', $date);
                                }),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators['start_date'] = 'Inicio desde: ' . \Carbon\Carbon::parse($data['start_date'])->format('d/m/Y');
                        }
                        if ($data['end_date'] ?? null) {
                            $indicators['end_date'] = 'Inicio hasta: ' . \Carbon\Carbon::parse($data['end_date'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Action::make('view_transaction')
                    ->label('')
                    // ->button()
                    ->tooltip('Ver transacción contable')
                    ->size('xs')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function ($record) {
                        $transaction = \App\Models\AccountingTransaction::where('campaign_reconciliation_id', $record->id)->first();
                        if ($transaction) {
                            return redirect()->route('filament.admin.resources.accounting-transactions.edit', $transaction->id);
                        } else {
                            Notification::make()
                                ->title('No hay transacción contable')
                                ->body('No se encontró una transacción contable para esta conciliación.')
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('')->size('xs')
                    ->tooltip('Rechazar conciliación')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->reconciliation_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar Conciliación')
                    ->modalDescription('¿Estás seguro de que quieres rechazar esta conciliación? Esta acción eliminará permanentemente el registro de la base de datos.')
                    ->modalSubmitActionLabel('Sí, Rechazar y Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function ($record) {
                        // Eliminar transacciones contables relacionadas
                        \App\Models\AccountingTransaction::where('campaign_reconciliation_id', $record->id)->delete();
                        
                        // Eliminar el registro de conciliación
                        $record->delete();
                        
                        Notification::make()
                            ->title('Conciliación rechazada y eliminada')
                            ->body('La conciliación ha sido rechazada y eliminada permanentemente de la base de datos.')
                            ->warning()
                            ->send();
                    }),

                

                Action::make('configure_profit')
                    ->label('Configurar Ganancia')
                    ->tooltip('Configurar ganancia para plan personalizado')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn ($record) => 
                        !$record->advertisingPlan && 
                        $record->reconciliation_data['plan_type'] === 'custom' &&
                        !isset($record->reconciliation_data['custom_plan_details']['client_price'])
                    )
                    ->form([
                        Forms\Components\TextInput::make('client_price')
                            ->label('Precio al Cliente ($)')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01)
                            ->step(0.01)
                            ->helperText('Ingresa el precio que pagará el cliente por este plan personalizado'),

                        Forms\Components\Toggle::make('paid_in_binance_rate')
                            ->label('Cliente pagó en tasa Binance')
                            ->helperText('Si el cliente pagó directamente en tasa Binance (no BCV), activa esta opción')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('conversion_note', 'El cliente pagó directamente en tasa Binance. No se aplicará conversión BCV→Binance.');
                                } else {
                                    $set('conversion_note', 'El cliente pagó en tasa BCV. Se aplicará conversión automática BCV→Binance.');
                                }
                            }),

                        Forms\Components\Placeholder::make('conversion_note')
                            ->label('')
                            ->content(fn ($get) => $get('paid_in_binance_rate') 
                                ? 'El cliente pagó directamente en tasa Binance. No se aplicará conversión BCV→Binance.'
                                : 'El cliente pagó en tasa BCV. Se aplicará conversión automática BCV→Binance.'
                            )
                            ->visible(fn ($get) => $get('paid_in_binance_rate') !== null),
                    ])
                    ->action(function ($record, array $data) {
                        $clientPrice = (float) $data['client_price'];
                        $paidInBinanceRate = (bool) ($data['paid_in_binance_rate'] ?? false);
                        $totalBudget = $record->planned_budget; // Presupuesto total del plan personalizado
                        $profitMargin = $clientPrice - $totalBudget; // Comisión = Cliente paga - Presupuesto total
                        $profitPercentage = $totalBudget > 0 ? ($profitMargin / $totalBudget) * 100 : 0;

                        // Actualizar los datos de la conciliación con la información del plan personalizado
                        $customPlanDetails = $record->reconciliation_data['custom_plan_details'] ?? [];
                        $customPlanDetails['client_price'] = $clientPrice;
                        $customPlanDetails['profit_margin'] = $profitMargin;
                        $customPlanDetails['profit_percentage'] = $profitPercentage;
                        $customPlanDetails['paid_in_binance_rate'] = $paidInBinanceRate;
                        $customPlanDetails['configured_at'] = now()->toISOString();

                        $reconciliationData = $record->reconciliation_data;
                        $reconciliationData['custom_plan_details'] = $customPlanDetails;

                        $record->update([
                            'reconciliation_data' => $reconciliationData,
                            'notes' => "📋 PLAN PERSONALIZADO CONFIGURADO - Presupuesto: $" . number_format($totalBudget, 2) . ", Precio Cliente: $" . number_format($clientPrice, 2) . ", Ganancia: $" . number_format($profitMargin, 2) . " (" . number_format($profitPercentage, 1) . "%)"
                        ]);

                        // Usar el servicio de conciliación para actualizar la transacción con detección automática
                        $reconciliationService = new \App\Services\CampaignReconciliationService();
                        
                        // Detectar automáticamente el nombre de Instagram del cliente
                        $instagramClientName = $reconciliationService->extractClientName($record->activeCampaign);
                        
                        // Obtener fechas reales de la campaña activa
                        $campaignStartDate = $record->activeCampaign->campaign_start_time?->format('Y-m-d');
                        $campaignEndDate = $record->activeCampaign->campaign_stop_time?->format('Y-m-d');

                        // Buscar la transacción contable existente para esta conciliación
                        $existingTransaction = \App\Models\AccountingTransaction::where('campaign_reconciliation_id', $record->id)->first();

                        if ($existingTransaction) {
                            // Actualizar la transacción existente con los nuevos valores Y datos automáticos
                            $existingTransaction->update([
                                'income' => $clientPrice, // Lo que paga el cliente
                                'expense' => $totalBudget, // Presupuesto total del plan
                                'profit' => $profitMargin, // Comisión (Cliente - Presupuesto)
                                'status' => 'completed', // ✅ CAMBIAR ESTADO A COMPLETADA
                                'client_name' => $instagramClientName, // Nombre de Instagram detectado automáticamente
                                'campaign_start_date' => $campaignStartDate, // Fecha real de inicio de campaña
                                'campaign_end_date' => $campaignEndDate, // Fecha real de final de campaña
                                'notes' => 'Plan personalizado configurado - Comisión calculada: Cliente $' . number_format($clientPrice, 2) . ' - Presupuesto $' . number_format($totalBudget, 2) . ' = Comisión $' . number_format($profitMargin, 2),
                                'metadata' => array_merge($existingTransaction->metadata ?? [], [
                                    'custom_plan_configured' => true,
                                    'configured_at' => now()->toISOString(),
                                    'client_price_configured' => $clientPrice,
                                    'profit_margin_configured' => $profitMargin,
                                    'paid_in_binance_rate' => $paidInBinanceRate,
                                    'instagram_detected' => $instagramClientName !== 'Cliente Sin Identificar',
                                    'campaign_dates' => [
                                        'start_date' => $campaignStartDate,
                                        'end_date' => $campaignEndDate,
                                        'duration_days' => $record->activeCampaign->getCampaignDurationDays()
                                    ]
                                ])
                            ]);
                        } else {
                            // Crear nueva transacción si no existe
                            \App\Models\AccountingTransaction::create([
                                'campaign_reconciliation_id' => $record->id,
                                'advertising_plan_id' => null, // Plan personalizado no tiene registro en AdvertisingPlan
                                'description' => "Plan personalizado configurado - {$record->reconciliation_data['plan_name']} - Cliente: {$instagramClientName}",
                                'income' => $clientPrice, // Lo que paga el cliente
                                'expense' => $totalBudget, // Presupuesto total del plan
                                'profit' => $profitMargin, // Comisión (Cliente - Presupuesto)
                                'currency' => 'USD',
                                'status' => 'completed',
                                'client_name' => $instagramClientName, // Nombre de Instagram detectado automáticamente
                                'meta_campaign_id' => $record->activeCampaign->meta_campaign_id,
                                'campaign_start_date' => $campaignStartDate, // Fecha real de inicio de campaña
                                'campaign_end_date' => $campaignEndDate, // Fecha real de final de campaña
                                'transaction_date' => now(),
                                'notes' => 'Plan personalizado configurado - Comisión calculada: Cliente $' . number_format($clientPrice, 2) . ' - Presupuesto $' . number_format($totalBudget, 2) . ' = Comisión $' . number_format($profitMargin, 2),
                                'metadata' => [
                                    'plan_name' => $record->reconciliation_data['plan_name'],
                                    'daily_budget' => $customPlanDetails['daily_budget'],
                                    'duration_days' => $customPlanDetails['duration_days'],
                                    'is_custom_plan' => true,
                                    'custom_plan_configured' => true,
                                    'configured_at' => now()->toISOString(),
                                    'client_price_configured' => $clientPrice,
                                    'profit_margin_configured' => $profitMargin,
                                    'paid_in_binance_rate' => $paidInBinanceRate,
                                    'instagram_detected' => $instagramClientName !== 'Cliente Sin Identificar',
                                    'campaign_dates' => [
                                        'start_date' => $campaignStartDate,
                                        'end_date' => $campaignEndDate,
                                        'duration_days' => $record->activeCampaign->getCampaignDurationDays()
                                    ],
                                    'created_via' => 'custom_plan_configuration'
                                ]
                            ]);
                        }

                        Notification::make()
                            ->title('Plan Personalizado Configurado')
                            ->body("✅ Cliente pagará: $" . number_format($clientPrice, 2) . 
                                  "\n💰 Presupuesto plan: $" . number_format($totalBudget, 2) . 
                                  "\n💵 Comisión: $" . number_format($profitMargin, 2) . " (" . number_format($profitPercentage, 1) . "%)")
                            ->success()
                            ->send();
                    }),

              
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
            ])
            ->defaultSort('created_at', 'desc');
            
                
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()
            ->with(['activeCampaign', 'advertisingPlan']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaignPlanReconciliations::route('/'),
            'create' => Pages\CreateCampaignPlanReconciliation::route('/create'),
            'edit' => Pages\EditCampaignPlanReconciliation::route('/{record}/edit'),
        ];
    }
}