<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingTransactionResource\Pages;
use App\Models\AccountingTransaction;
use App\Models\CampaignReconciliation;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Get;
use Filament\Forms\Set;

class AccountingTransactionResource extends Resource
{
    protected static ?string $model = AccountingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transacciones Contables';

    protected static ?string $modelLabel = 'Transacción Contable';

    protected static ?string $pluralModelLabel = 'Transacciones Contables';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Transacción')
                    ->description('Datos básicos de la transacción contable')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('transaction_type')
                                    ->label('Tipo de Transacción')
                                    ->required()
                                    ->options([
                                        'income' => 'Ingreso',
                                        'expense' => 'Gasto',
                                        'profit' => 'Ganancia',
                                        'refund' => 'Reembolso',
                                    ])
                                    ->placeholder('Selecciona el tipo')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('description', '');
                                    }),

                                TextInput::make('amount')
                                    ->label('Monto ($)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('29.00'),

                                Select::make('currency')
                                    ->label('Moneda')
                                    ->required()
                                    ->options([
                                        'USD' => 'Dólar Estadounidense (USD)',
                                        'EUR' => 'Euro (EUR)',
                                        'MXN' => 'Peso Mexicano (MXN)',
                                    ])
                                    ->default('USD')
                                    ->placeholder('Selecciona la moneda'),

                                Select::make('status')
                                    ->label('Estado')
                                    ->required()
                                    ->options([
                                        'pending' => 'Pendiente',
                                        'completed' => 'Completada',
                                        'cancelled' => 'Cancelada',
                                        'refunded' => 'Reembolsada',
                                    ])
                                    ->default('pending')
                                    ->placeholder('Selecciona el estado'),
                            ]),
                    ]),

                Section::make('Relaciones del Sistema')
                    ->description('Conexión con campañas y planes de publicidad')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('campaign_reconciliation_id')
                                    ->label('Conciliación de Campaña')
                                    ->options(CampaignReconciliation::pluck('meta_campaign_name', 'id'))
                                    ->searchable()
                                    ->placeholder('Selecciona una conciliación (opcional)')
                                    ->helperText('Conecta esta transacción con una campaña específica'),

                                Select::make('advertising_plan_id')
                                    ->label('Plan de Publicidad')
                                    ->options(AdvertisingPlan::active()->pluck('plan_name', 'id'))
                                    ->searchable()
                                    ->placeholder('Selecciona un plan (opcional)')
                                    ->helperText('Conecta esta transacción con un plan específico'),
                            ]),
                    ]),

                Section::make('Información del Cliente')
                    ->description('Datos del cliente y campaña de Meta')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('client_name')
                                    ->label('Nombre del Cliente')
                                    ->maxLength(255)
                                    ->placeholder('BrandShop')
                                    ->helperText('Nombre de la empresa o marca del cliente'),

                                TextInput::make('meta_campaign_id')
                                    ->label('ID de Campaña Meta')
                                    ->maxLength(255)
                                    ->placeholder('123456789012345')
                                    ->helperText('ID de la campaña en Meta Ads (opcional)'),
                            ]),
                    ]),

                Section::make('Fechas y Referencias')
                    ->description('Control temporal y referencias de la transacción')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('transaction_date')
                                    ->label('Fecha de Transacción')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->placeholder('Selecciona la fecha'),

                                DatePicker::make('due_date')
                                    ->label('Fecha de Vencimiento')
                                    ->minDate(fn (Get $get) => $get('transaction_date'))
                                    ->placeholder('Fecha de vencimiento (opcional)'),
                            ]),

                        TextInput::make('reference_number')
                            ->label('Número de Referencia')
                            ->maxLength(255)
                            ->placeholder('REF-2024-001')
                            ->helperText('Número de referencia interno o externo'),
                    ]),

                Section::make('Descripción y Notas')
                    ->description('Detalles adicionales de la transacción')
                    ->schema([
                        TextInput::make('description')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Descripción de la transacción...')
                            ->helperText('Descripción detallada de la transacción'),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Notas adicionales...')
                            ->maxLength(1000)
                            ->helperText('Notas internas sobre la transacción'),

                        KeyValue::make('metadata')
                            ->label('Metadatos')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->addActionLabel('Agregar Campo')
                            ->deleteActionLabel('Eliminar Campo')
                            ->helperText('Datos adicionales en formato clave-valor'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'profit' => 'warning',
                        'refund' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                        'profit' => 'Ganancia',
                        'refund' => 'Reembolso',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable()
                    ->color(fn (AccountingTransaction $record): string => match($record->transaction_type) {
                        'income', 'profit' => 'success',
                        'expense', 'refund' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('client_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                TextColumn::make('meta_campaign_id')
                    ->label('Campaña Meta')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copiado al portapapeles')
                    ->limit(15),

                TextColumn::make('transaction_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Pendiente',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        'refunded' => 'Reembolsada',
                        default => $state,
                    }),

                TextColumn::make('campaignReconciliation.meta_campaign_name')
                    ->label('Campaña')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('advertisingPlan.plan_name')
                    ->label('Plan')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Tipo de Transacción')
                    ->options([
                        'income' => 'Ingreso',
                        'expense' => 'Gasto',
                        'profit' => 'Ganancia',
                        'refund' => 'Reembolso',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        'refunded' => 'Reembolsada',
                    ]),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('Desde'),
                        DatePicker::make('to_date')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
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
            ->defaultSort('transaction_date', 'desc');
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
            'index' => Pages\ListAccountingTransactions::route('/'),
            'create' => Pages\CreateAccountingTransaction::route('/create'),
            'edit' => Pages\EditAccountingTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
