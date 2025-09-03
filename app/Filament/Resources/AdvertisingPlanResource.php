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
use Filament\Tables\Columns\TextColumn as TableTextColumn;
use Filament\Forms\Get;
use Filament\Forms\Set;

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
                                        $this->calculatePlanTotals($set, $get);
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
                                        $this->calculatePlanTotals($set, $get);
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
