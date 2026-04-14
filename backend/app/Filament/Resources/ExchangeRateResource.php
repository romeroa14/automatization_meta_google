<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Tasas de Cambio';

    protected static ?string $modelLabel = 'Tasa de Cambio';

    protected static ?string $pluralModelLabel = 'Tasas de Cambio';

    protected static ?string $navigationGroup = 'Tasas de USD';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Tasa')
                    ->schema([
                        Select::make('currency_code')
                            ->label('Moneda')
                            ->options([
                                'USD' => 'USD - Dólar Estadounidense',
                                'EUR' => 'EUR - Euro',
                                'CNY' => 'CNY - Yuan Chino',
                                'TRY' => 'TRY - Lira Turca',
                                'RUB' => 'RUB - Rublo Ruso',
                            ])
                            ->required()
                            ->searchable(),

                        TextInput::make('rate')
                            ->label('Tasa de Cambio')
                            ->numeric()
                            ->step(0.00000001)
                            ->required()
                            ->suffix('Bs.'),

                        Select::make('source')
                            ->label('Fuente')
                            ->options([
                                'BCV' => 'BCV - Banco Central de Venezuela',
                                'BINANCE' => 'Binance - Exchange Monitor',
                            ])
                            ->required()
                            ->searchable(),

                        TextInput::make('target_currency')
                            ->label('Moneda Objetivo')
                            ->default('VES')
                            ->disabled()
                            ->dehydrated(false),

                        DateTimePicker::make('fetched_at')
                            ->label('Fecha de Obtención')
                            ->required()
                            ->default(now()),

                        Toggle::make('is_valid')
                            ->label('Tasa Válida')
                            ->default(true),

                        TextInput::make('error_message')
                            ->label('Mensaje de Error')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && !$record->is_valid),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currency_code')
                    ->label('Moneda')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'USD' => 'success',
                        'EUR' => 'info',
                        'CNY' => 'warning',
                        'TRY' => 'danger',
                        'RUB' => 'gray',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('rate')
                    ->label('Tasa')
                    ->money('VES')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                BadgeColumn::make('source')
                    ->label('Fuente')
                    ->colors([
                        'primary' => 'BCV',
                        'success' => 'BINANCE',
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fetched_at')
                    ->label('Obtenida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('info'),

                TextColumn::make('time_ago')
                    ->label('Hace')
                    ->getStateUsing(fn ($record) => $record->fetched_at->diffForHumans())
                    ->color('gray')
                    ->sortable(),

                BadgeColumn::make('is_valid')
                    ->label('Estado')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Válida' : 'Error')
                    ->sortable(),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(30)
                    ->color('danger')
                    ->visible(fn ($record) => $record && !$record->is_valid)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->label('Fuente')
                    ->options([
                        'BCV' => 'BCV',
                        'BINANCE' => 'Binance',
                    ]),

                Tables\Filters\SelectFilter::make('currency_code')
                    ->label('Moneda')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'CNY' => 'CNY',
                        'TRY' => 'TRY',
                        'RUB' => 'RUB',
                    ]),

                Tables\Filters\TernaryFilter::make('is_valid')
                    ->label('Estado')
                    ->placeholder('Todas las tasas')
                    ->trueLabel('Solo válidas')
                    ->falseLabel('Solo con error'),

                Tables\Filters\Filter::make('recent')
                    ->label('Últimas 24 horas')
                    ->query(fn (Builder $query): Builder => $query->where('fetched_at', '>=', now()->subDay())),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Ver detalles')
                    ->modalHeading('Detalles de la Tasa')
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <strong>Moneda:</strong> ' . $record->currency_code . '
                                </div>
                                <div>
                                    <strong>Fuente:</strong> ' . $record->source . '
                                </div>
                                <div>
                                    <strong>Tasa:</strong> ' . number_format($record->rate, 2, ',', '.') . ' Bs.
                                </div>
                                <div>
                                    <strong>Estado:</strong> ' . ($record->is_valid ? 'Válida' : 'Error') . '
                                </div>
                                <div>
                                    <strong>Actualizada:</strong> ' . $record->fetched_at->format('d/m/Y H:i:s') . '
                                </div>
                                <div>
                                    <strong>Hace:</strong> ' . $record->fetched_at->diffForHumans() . '
                                </div>
                            </div>
                            ' . ($record->error_message ? '<div class="mt-4 p-3 bg-red-50 border border-red-200 rounded"><strong>Error:</strong> ' . $record->error_message . '</div>' : '') . '
                        </div>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Action::make('calculate_price')
                    ->label('')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->tooltip('Calcular precio')
                    ->visible(fn ($record) => $record && $record->is_valid && $record->currency_code === 'USD')
                    ->form([
                        TextInput::make('usd_price')
                            ->label('Precio en USD')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->required()
                            ->default(6.00),
                    ])
                    ->action(function ($record, array $data) {
                        $usdPrice = (float) $data['usd_price'];
                        
                        if ($record->source === 'BCV') {
                            $vesPrice = $usdPrice * $record->rate;
                            $message = "Precio en VES: " . number_format($vesPrice, 2, ',', '.') . " Bs.";
                        } else {
                            // Para Binance, calcular precio BCV
                            $bcvPrice = \App\Models\ExchangeRate::calculateBcvPriceFromBinance($usdPrice);
                            if ($bcvPrice) {
                                $message = "Precio BCV: " . number_format($bcvPrice, 2, ',', '.') . " Bs.";
                            } else {
                                $message = "No se pudo calcular el precio BCV";
                            }
                        }
                        
                        Notification::make()
                            ->title('Cálculo de Precio')
                            ->body($message)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    BulkAction::make('update_rates')
                        ->label('Actualizar Tasas')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->action(function () {
                            try {
                                Artisan::call('exchange:update-all', ['currency' => 'USD']);
                                
                                Notification::make()
                                    ->title('Tasas Actualizadas')
                                    ->body('Las tasas de cambio se han actualizado exitosamente.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al Actualizar')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Actualizar Tasas de Cambio')
                        ->modalDescription('¿Estás seguro de que quieres actualizar todas las tasas de cambio? Esto puede tomar unos segundos.')
                        ->modalSubmitActionLabel('Sí, Actualizar'),
                ])
            ])
            ->defaultSort('fetched_at', 'desc')
            ->poll('30s'); // Actualizar cada 30 segundos
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()
            ->latest('fetched_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit' => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }
}