<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisingPlanResource\Pages;
use App\Models\AdvertisingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;

class AdvertisingPlanResource extends Resource
{
    protected static ?string $model = AdvertisingPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Planes de Publicidad';

    protected static ?string $modelLabel = 'Plan de Publicidad';

    protected static ?string $pluralModelLabel = 'Planes de Publicidad';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Plan')
                    ->description('Configura los detalles básicos del plan de publicidad')
                    ->schema([
                        TextInput::make('plan_name')
                            ->label('Nombre del Plan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Plan Básico 7 Días')
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('description', 'Plan de publicidad personalizado');
                            }),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Describe las características del plan...')
                            ->maxLength(1000),

                        Toggle::make('is_active')
                            ->label('Plan Activo')
                            ->default(true)
                            ->helperText('Activa o desactiva este plan de publicidad'),
                    ])->columns(2),

                Section::make('Configuración Financiera')
                    ->description('Define los precios y presupuestos del plan')
                    ->schema([
                        Grid::make(2)
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
                                        self::calculatePlanTotals($set, $get);
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
                                        self::calculatePlanTotals($set, $get);
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
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

                                TextInput::make('client_price')
                                    ->label('Precio al Cliente ($)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('29.00')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::calculatePlanTotals($set, $get);
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('profit_margin')
                                    ->label('Ganancia ($)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('8.00')
                                    ->disabled()
                                    ->helperText('Calculado automáticamente'),

                                TextInput::make('profit_percentage')
                                    ->label('Porcentaje de Ganancia (%)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('38.10')
                                    ->disabled()
                                    ->helperText('Calculado automáticamente'),
                            ]),
                    ]),

                Section::make('Características del Plan')
                    ->description('Define las características y servicios incluidos')
                    ->schema([
                        KeyValue::make('features')
                            ->label('Características')
                            ->keyLabel('Característica')
                            ->valueLabel('Descripción')
                            ->addActionLabel('Agregar Característica')
                            ->deleteActionLabel('Eliminar Característica')
                            ->reorderable()
                            ->helperText('Agrega las características que incluye este plan (ej: Facebook Ads, Instagram Ads, Reportes, etc.)'),
                    ]),

                Section::make('Resumen del Plan')
                    ->description('Vista previa de la configuración del plan')
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextInput::make('plan_summary')
                                    ->label('Resumen')
                                    ->disabled()
                                    ->helperText('Resumen automático del plan')
                                    ->placeholder('Plan de $3.00 diarios por 7 días = $21.00 presupuesto, precio cliente $29.00, ganancia $8.00 (38.10%)'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_name')
                    ->label('Nombre del Plan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

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

                TextColumn::make('profit_percentage')
                    ->label('Margen %')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1) . '%'),

                TextColumn::make('price_in_binance')
                    ->label('Precio Binance')
                    ->getStateUsing(function ($record) {
                        $binancePrice = \App\Models\ExchangeRate::calculatePlanPriceInBinance($record->total_budget);
                        return $binancePrice ? number_format($binancePrice, 2, ',', '.') . ' Bs.' : 'N/A';
                    })
                    ->color('warning')
                    ->sortable(false),

                TextColumn::make('price_in_bcv')
                    ->label('Precio BCV')
                    ->getStateUsing(function ($record) {
                        $bcvPrice = \App\Models\ExchangeRate::calculatePlanPriceInBcv($record->total_budget);
                        return $bcvPrice ? number_format($bcvPrice, 2, ',', '.') . ' Bs.' : 'N/A';
                    })
                    ->color('info')
                    ->sortable(false),

                IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
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
                Tables\Actions\Action::make('view_prices')
                    ->label('')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->tooltip('Ver precios en ambas tasas')
                    ->modalHeading('Precios del Plan en Ambas Tasas')
                    ->modalContent(function ($record) {
                        $equivalents = \App\Models\ExchangeRate::calculatePlanPriceEquivalents($record->total_budget);
                        $stats = \App\Models\ExchangeRate::getPlanPriceStatistics();
                        
                        if (!$equivalents || empty($stats)) {
                            return new HtmlString('<div class="p-4 text-center text-gray-500">No se pudieron obtener las tasas de cambio</div>');
                        }
                        
                        return new HtmlString(
                            '<div class="space-y-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">📊 Información del Plan</h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>Plan:</strong> ' . $record->plan_name . '</div>
                                        <div><strong>Presupuesto:</strong> $' . number_format($record->total_budget, 2) . ' USD</div>
                                        <div><strong>Precio Cliente:</strong> $' . number_format($record->client_price, 2) . ' USD</div>
                                        <div><strong>Ganancia:</strong> $' . number_format($record->profit_margin, 2) . ' USD</div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">💰 Precio en Binance</h4>
                                        <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                                            ' . number_format($equivalents['binance_price'], 2, ',', '.') . ' Bs.
                                        </div>
                                        <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                            Tasa: ' . number_format($equivalents['binance_rate'], 2, ',', '.') . ' Bs.
                                        </div>
                                    </div>
                                    
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">🏛️ Precio en BCV</h4>
                                        <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                            ' . number_format($equivalents['bcv_price'], 2, ',', '.') . ' Bs.
                                        </div>
                                        <div class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                            Tasa: ' . number_format($equivalents['bcv_rate'], 2, ',', '.') . ' Bs.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 dark:bg-gray-900/20 p-4 rounded-lg border border-gray-200 dark:border-gray-800">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">📈 Estadísticas de Conversión</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>Factor de Conversión:</strong> ' . number_format($equivalents['conversion_factor'], 3) . 'x</div>
                                        <div><strong>Diferencia:</strong> ' . number_format($stats['difference'], 2, ',', '.') . ' Bs.</div>
                                        <div><strong>Diferencia %:</strong> ' . number_format($stats['percentage_difference'], 1) . '%</div>
                                        <div><strong>Última Actualización:</strong> ' . $stats['last_updated']->format('d/m/Y H:i') . '</div>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-2">💡 Explicación</h4>
                                    <div class="text-sm text-green-800 dark:text-green-200">
                                        <p><strong>Precio Binance:</strong> Es el costo real en bolívares para pagar a Meta usando Binance.</p>
                                        <p><strong>Precio BCV:</strong> Es el precio que cobras al cliente usando la tasa oficial BCV.</p>
                                        <p><strong>Fórmula:</strong> Precio BCV = Presupuesto USD × (Tasa Binance ÷ Tasa BCV)</p>
                                    </div>
                                </div>
                            </div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                    
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
            'index' => Pages\ListAdvertisingPlans::route('/'),
            'create' => Pages\CreateAdvertisingPlan::route('/create'),
            'edit' => Pages\EditAdvertisingPlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Calcular totales del plan automáticamente
     */
    private static function calculatePlanTotals(Set $set, Get $get): void
    {
        $dailyBudget = (float) ($get('daily_budget') ?? 0);
        $durationDays = (int) ($get('duration_days') ?? 0);
        $clientPrice = (float) ($get('client_price') ?? 0);

        // Calcular presupuesto total
        $totalBudget = $dailyBudget * $durationDays;
        $set('total_budget', $totalBudget);

        // Calcular ganancia
        $profitMargin = $clientPrice - $totalBudget;
        $set('profit_margin', $profitMargin);

        // Calcular porcentaje de ganancia
        $profitPercentage = $totalBudget > 0 ? ($profitMargin / $totalBudget) * 100 : 0;
        $set('profit_percentage', $profitPercentage);

        // Actualizar resumen
        $summary = "Plan de $" . number_format($dailyBudget, 2) . " diarios por {$durationDays} días = $" . number_format($totalBudget, 2) . " presupuesto, precio cliente $" . number_format($clientPrice, 2) . ", ganancia $" . number_format($profitMargin, 2) . " (" . number_format($profitPercentage, 1) . "%)";
        $set('plan_summary', $summary);
    }
}
