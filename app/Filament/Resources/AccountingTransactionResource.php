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
use Filament\Actions\Action;
use Filament\Actions\Modal\Actions\Action as ActionsAction;

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
                    ->schema([
                        TextInput::make('client_name')
                            ->label('Cliente (Cuenta de Instagram)')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Nombre de la cuenta de Instagram del cliente (se detecta automáticamente)')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('income')
                            ->label('Ingreso ($)')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Monto que paga el cliente por el plan'),

                        TextInput::make('expense')
                            ->label('Gasto ($)')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Presupuesto gastado en Meta Ads'),

                        TextInput::make('profit')
                            ->label('Ganancia ($)')
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Ganancia = Ingreso - Gasto')
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $income = (float) $get('income');
                                $expense = (float) $get('expense');
                                $calculatedProfit = $income - $expense;
                                if ($state != $calculatedProfit) {
                                    $set('profit', $calculatedProfit);
                                }
                            }),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'completed' => 'Completada',
                                'cancelled' => 'Cancelada',
                                'refunded' => 'Reembolsada',
                                'paused' => 'Pausada',
                            ])
                            ->default('pending'),

                        DatePicker::make('campaign_start_date')
                            ->label('Inicio de Campaña')
                            ->helperText('Fecha de inicio de la campaña publicitaria'),

                        DatePicker::make('campaign_end_date')
                            ->label('Final de Campaña')
                            ->helperText('Fecha de finalización de la campaña publicitaria'),

                        DatePicker::make('transaction_date')
                            ->label('Fecha de Transacción')
                            ->default(now())
                            ->required(),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Notas adicionales sobre esta transacción'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('Cliente (Instagram)')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('income')
                    ->label('Ingreso')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('expense')
                    ->label('Gasto')
                    ->money('USD')
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('profit')
                    ->label('Ganancia')
                    ->money('USD')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('real_profit_binance')
                    ->label('Ganancia Real Binance')
                    ->getStateUsing(function ($record) {
                        // Verificar si el cliente pagó directamente en tasa Binance
                        $paidInBinanceRate = $record->metadata['paid_in_binance_rate'] ?? false;
                        
                        if ($paidInBinanceRate) {
                            // Si pagó en Binance, la ganancia real es la misma que la tradicional
                            return '$' . number_format($record->profit, 2);
                        } else {
                            // Si pagó en BCV, aplicar conversión matemática
                            $realProfit = \App\Models\ExchangeRate::calculateRealProfitInUsd($record->income, $record->expense);
                            return $realProfit ? '$' . number_format($realProfit, 2) : 'N/A';
                        }
                    })
                    ->color('success')
                    ->sortable(false)
                    ->tooltip(function ($record) {
                        $paidInBinanceRate = $record->metadata['paid_in_binance_rate'] ?? false;
                        return $paidInBinanceRate 
                            ? 'Cliente pagó en tasa Binance - Sin conversión aplicada'
                            : 'Cliente pagó en tasa BCV - Conversión BCV→Binance aplicada';
                    }),

                TextColumn::make('campaign_start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('info'),

                TextColumn::make('campaign_end_date')
                    ->label('Final')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('transaction_date')
                    ->label('Fecha Transacción')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => ['cancelled', 'paused'],
                        'info' => 'refunded',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => '⏳ Pendiente',
                        'completed' => '✅ Completada',
                        'cancelled' => '❌ Cancelada',
                        'refunded' => '🔄 Reembolsada',
                        'paused' => '⏸️ Pausada',
                        default => '❓ Desconocido',
                    }),

             
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        'refunded' => 'Reembolsada',
                        'paused' => 'Pausada',
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
                Tables\Actions\Action::make('view_conversion_details')
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->tooltip('Ver detalles de conversión BCV→Binance')
                    ->modalHeading('Detalles de Conversión BCV → Binance')
                    ->modalContent(function ($record) {
                        $paidInBinanceRate = $record->metadata['paid_in_binance_rate'] ?? false;
                        $completeEquivalents = \App\Models\ExchangeRate::calculateCompletePlanEquivalents($record->expense, $record->income);
                        
                        if (!$completeEquivalents) {
                            return new \Illuminate\Support\HtmlString('<div class="p-4 text-center text-gray-500">No se pudieron obtener las tasas de cambio</div>');
                        }
                        
                        return new \Illuminate\Support\HtmlString(
                            '<div class="space-y-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">📊 Información de la Transacción</h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>Cliente:</strong> ' . $record->client_name . '</div>
                                        <div><strong>Ingreso:</strong> $' . number_format($record->income, 2) . ' USD</div>
                                        <div><strong>Gasto:</strong> $' . number_format($record->expense, 2) . ' USD</div>
                                        <div><strong>Ganancia tradicional:</strong> $' . number_format($record->profit, 2) . ' USD</div>
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                                    <h4 class="font-semibold text-green-900 dark:text-green-100 mb-2">💰 ' . ($paidInBinanceRate ? 'Pago Directo en Binance' : 'Conversión Real BCV → Binance') . '</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        ' . ($paidInBinanceRate ? '
                                        <div><strong>Cliente pagó directamente:</strong> $' . number_format($record->income, 2) . ' USD</div>
                                        <div><strong>En tasa Binance:</strong> ' . number_format($record->income * $completeEquivalents['rates']['binance_rate'], 2, ',', '.') . ' Bs.</div>
                                        <div><strong>Gasto en Meta:</strong> $' . number_format($record->expense, 2) . '</div>
                                        <div><strong>Ganancia real:</strong> $' . number_format($record->profit, 2) . '</div>
                                        <div class="col-span-2"><strong>Margen real:</strong> ' . number_format(($record->profit / $record->expense) * 100, 1) . '%</div>
                                        ' : '
                                        <div><strong>Cliente paga en BCV:</strong> ' . number_format($completeEquivalents['real_profit']['client_payment_bcv'], 2, ',', '.') . ' Bs.</div>
                                        <div><strong>USD reales recibidos:</strong> $' . number_format($completeEquivalents['real_profit']['real_usd_received'], 2) . '</div>
                                        <div><strong>Gasto en Meta:</strong> $' . number_format($record->expense, 2) . '</div>
                                        <div><strong>Ganancia real:</strong> $' . number_format($completeEquivalents['real_profit']['real_profit_usd'], 2) . '</div>
                                        <div class="col-span-2"><strong>Margen real:</strong> ' . number_format($completeEquivalents['real_profit']['profit_percentage'], 1) . '%</div>
                                        ') . '
                                    </div>
                                </div>
                                
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                    <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">📈 Tasas Utilizadas</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div><strong>Tasa BCV:</strong> ' . number_format($completeEquivalents['rates']['bcv_rate'], 2, ',', '.') . ' Bs./USD</div>
                                        <div><strong>Tasa Binance:</strong> ' . number_format($completeEquivalents['rates']['binance_rate'], 2, ',', '.') . ' Bs./USD</div>
                                        <div class="col-span-2"><strong>Factor de conversión:</strong> ' . number_format($completeEquivalents['traditional']['conversion_factor'], 3) . 'x</div>
                                    </div>
                                </div>
                                
                                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                                    <h4 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">💡 Explicación</h4>
                                    <div class="text-sm text-purple-800 dark:text-purple-200">
                                        ' . ($paidInBinanceRate ? '
                                        <p><strong>✅ Pago Directo en Binance:</strong> El cliente pagó directamente en tasa Binance, por lo que no se aplica conversión matemática. La ganancia real es igual a la ganancia tradicional.</p>
                                        <p><strong>💰 Flujo:</strong> Cliente paga $' . number_format($record->income, 2) . ' USD → Tú pagas $' . number_format($record->expense, 2) . ' USD a Meta → Ganancia: $' . number_format($record->profit, 2) . ' USD</p>
                                        ' : '
                                        <p><strong>🔄 Conversión BCV→Binance:</strong> El cliente pagó en tasa BCV, pero tú necesitas comprar USD en Binance para pagar a Meta.</p>
                                        <p><strong>💰 Flujo:</strong> Cliente paga $' . number_format($record->income, 2) . ' USD a tasa BCV → Recibes ' . number_format($completeEquivalents['real_profit']['client_payment_bcv'], 2, ',', '.') . ' Bs. → Compras $' . number_format($completeEquivalents['real_profit']['real_usd_received'], 2) . ' USD en Binance → Pagas $' . number_format($record->expense, 2) . ' USD a Meta → Ganancia real: $' . number_format($completeEquivalents['real_profit']['real_profit_usd'], 2) . ' USD</p>
                                        ') . '
                                    </div>
                                </div>
                            </div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\Action::make('view_reconciliation')
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->campaign_reconciliation_id)
                    ->url(fn ($record) => route('filament.admin.resources.campaign-plan-reconciliations.edit', $record->campaign_reconciliation_id))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-pencil'),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        // Ya no necesitamos agrupar porque ahora solo hay un registro por conciliación
        return parent::getEloquentQuery()
            ->orderBy('transaction_date', 'desc');
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

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }

}
