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

             
            ])
            ->filters([
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
