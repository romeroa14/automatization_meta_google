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

    protected static ?int $navigationSort = 3;

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
                TextColumn::make('reconciliation_status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => '⏳',
                        'approved' => '✅',
                        'rejected' => '❌',
                        default => '❓',
                    }),

                TextColumn::make('activeCampaign.meta_campaign_name')
                    ->label('Campaña')
                    ->searchable()
                    ->sortable()
                    ->limit(10)
                    ->weight('bold'),

                TextColumn::make('advertisingPlan.plan_name')
                    ->label('Plan de Publicidad')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                

                    TextColumn::make('activeCampaign.campaign_total_budget')
                    ->label('Presupuesto Total')
                    ->getStateUsing(function ($record) {
                        // Usar el mismo cálculo que ActiveCampaignsResource
                        $dailyBudget = $record->activeCampaign->campaign_daily_budget ?? $record->activeCampaign->adset_daily_budget;
                        $duration = $record->activeCampaign->getCampaignDurationDays() ?? $record->activeCampaign->getAdsetDurationDays();
                        
                        if ($dailyBudget && $duration) {
                            // Meta API devuelve valores en centavos, dividir entre 100
                            return ($dailyBudget / 100) * $duration;
                        }
                        
                        return 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                
                    TextColumn::make('activeCampaign.campaign_daily_budget')
                    ->label('Presupuesto Diario')
                    ->getStateUsing(function ($record) {
                        // Usar el mismo cálculo que ActiveCampaignsResource
                        $dailyBudget = $record->activeCampaign->campaign_daily_budget ?? $record->activeCampaign->adset_daily_budget;
                        return $dailyBudget ? $dailyBudget / 100 : 0;
                    })
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                
                
                
                

                

                

               


                

                
                

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
                    ]),

                Tables\Filters\SelectFilter::make('advertising_plan_id')
                    ->label('Plan de Publicidad')
                    ->relationship('advertisingPlan', 'plan_name'),
            ])
            ->actions([
                Action::make('view_transaction')
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-banknotes')
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
                    ->label('')->button()->size('xs')
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

                Action::make('delete_reconciliation')
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->reconciliation_status, ['completed', 'approved']))
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar Conciliación')
                    ->modalDescription('¿Estás seguro de que quieres eliminar esta conciliación? Esta acción eliminará permanentemente el registro y todas las transacciones contables relacionadas.')
                    ->modalSubmitActionLabel('Sí, Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function ($record) {
                        // Eliminar transacciones contables relacionadas
                        \App\Models\AccountingTransaction::where('campaign_reconciliation_id', $record->id)->delete();
                        
                        // Eliminar el registro de conciliación
                        $record->delete();
                        
                        Notification::make( )
                            ->title('Conciliación eliminada')
                            ->body('La conciliación y todas las transacciones relacionadas han sido eliminadas permanentemente.')
                            ->danger()
                            ->send();
                    }),

                Action::make('configure_profit')
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn ($record) => str_contains($record->advertisingPlan->plan_name, 'Plan Personalizado') && $record->advertisingPlan->profit_margin == 0)
                    ->form([
                        Forms\Components\TextInput::make('client_price')
                            ->label('Precio al Cliente ($)')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0.01)
                            ->step(0.01)
                            ->default(fn ($record) => $record->advertisingPlan->client_price)
                            ->helperText('Ingresa el precio que pagará el cliente por este plan personalizado'),
                    ])
                    ->action(function ($record, array $data) {
                        $plan = $record->advertisingPlan;
                        $clientPrice = (float) $data['client_price'];
                        $totalBudget = $plan->total_budget; // Presupuesto total del plan
                        $profitMargin = $clientPrice - $totalBudget; // Comisión = Cliente paga - Presupuesto total
                        $profitPercentage = $totalBudget > 0 ? ($profitMargin / $totalBudget) * 100 : 0;

                        // Actualizar el plan con el nuevo precio del cliente y ganancia
                        $plan->update([
                            'client_price' => $clientPrice,
                            'profit_margin' => $profitMargin,
                            'profit_percentage' => $profitPercentage,
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
                                'client_name' => $instagramClientName, // Nombre de Instagram detectado automáticamente
                                'campaign_start_date' => $campaignStartDate, // Fecha real de inicio de campaña
                                'campaign_end_date' => $campaignEndDate, // Fecha real de final de campaña
                                'notes' => 'Plan personalizado configurado - Comisión calculada: Cliente $' . number_format($clientPrice, 2) . ' - Presupuesto $' . number_format($totalBudget, 2) . ' = Comisión $' . number_format($profitMargin, 2),
                                'metadata' => array_merge($existingTransaction->metadata ?? [], [
                                    'custom_plan_configured' => true,
                                    'configured_at' => now()->toISOString(),
                                    'client_price_configured' => $clientPrice,
                                    'profit_margin_configured' => $profitMargin,
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
                                'advertising_plan_id' => $plan->id,
                                'description' => "Plan personalizado configurado - {$plan->plan_name} - Cliente: {$instagramClientName}",
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
                                    'plan_name' => $plan->plan_name,
                                    'daily_budget' => $plan->daily_budget,
                                    'duration_days' => $plan->duration_days,
                                    'is_custom_plan' => true,
                                    'custom_plan_configured' => true,
                                    'configured_at' => now()->toISOString(),
                                    'client_price_configured' => $clientPrice,
                                    'profit_margin_configured' => $profitMargin,
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