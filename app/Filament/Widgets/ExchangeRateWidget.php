<?php

namespace App\Filament\Widgets;

use App\Models\ExchangeRate;
use Filament\Widgets\Widget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;

class ExchangeRateWidget extends BaseWidget implements HasActions
{
    use InteractsWithActions, InteractsWithForms;

    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;
    
    protected static bool $isLazy = false;
    
    // Solo mostrar en la página de tasas, no en el dashboard principal
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.exchange-rates-page');
    }

    protected function getStats(): array
    {
        $latestBcv = ExchangeRate::getLatestRate('USD', 'BCV');
        $latestBinance = ExchangeRate::getLatestRate('USD', 'BINANCE');
        
        $stats = [];
        
        // Tasa BCV
        if ($latestBcv) {
            $stats[] = Stat::make('Tasa BCV (USD)', number_format($latestBcv->rate, 2, ',', '.') . ' Bs.')
                ->description('Banco Central de Venezuela')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'this.style.transform = "scale(1.05)"; setTimeout(() => this.style.transform = "scale(1)", 200);'
                ])
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]);
        }
        
        // Tasa Binance
        if ($latestBinance) {
            $stats[] = Stat::make('Tasa Binance (USD)', number_format($latestBinance->rate, 2, ',', '.') . ' Bs.')
                ->description('Exchange Monitor')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'this.style.transform = "scale(1.05)"; setTimeout(() => this.style.transform = "scale(1)", 200);'
                ])
                ->chart([3, 7, 2, 4, 8, 2, 6, 4]);
        }
        
        // Diferencia entre tasas
        if ($latestBcv && $latestBinance) {
            $difference = $latestBinance->rate - $latestBcv->rate;
            $percentageDiff = ($difference / $latestBcv->rate) * 100;
            
            $stats[] = Stat::make('Diferencia', number_format($difference, 2, ',', '.') . ' Bs.')
                ->description(number_format($percentageDiff, 1) . '% más alto en Binance')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($percentageDiff > 40 ? 'danger' : ($percentageDiff > 30 ? 'warning' : 'info'))
                ->chart([2, 3, 4, 3, 5, 4, 6, 5]);
        }
        
        // Última actualización
        $lastUpdate = $latestBcv?->fetched_at ?? $latestBinance?->fetched_at;
        if ($lastUpdate) {
            $stats[] = Stat::make('Última Actualización', $lastUpdate->diffForHumans())
                ->description($lastUpdate->format('d/m/Y H:i'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray')
                ->chart([1, 1, 1, 1, 1, 1, 1, 1]);
        }
        
        return $stats;
    }

    protected function getActions(): array
    {
        return [
            Action::make('update_rates')
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
                ->modalDescription('¿Estás seguro de que quieres actualizar todas las tasas de cambio?')
                ->modalSubmitActionLabel('Sí, Actualizar'),
                
            Action::make('calculate_price')
                ->label('Calcular Precio')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\TextInput::make('usd_price')
                        ->label('Precio en USD')
                        ->numeric()
                        ->step(0.01)
                        ->prefix('$')
                        ->required()
                        ->default(6.00),
                ])
                ->action(function (array $data) {
                    $usdPrice = (float) $data['usd_price'];
                    $bcvPrice = ExchangeRate::calculateBcvPriceFromBinance($usdPrice);
                    
                    if ($bcvPrice) {
                        $latestBcv = ExchangeRate::getLatestRate('USD', 'BCV');
                        $latestBinance = ExchangeRate::getLatestRate('USD', 'BINANCE');
                        
                        $message = "💰 Cálculo de Precio BCV:\n\n";
                        $message .= "Precio original: $" . number_format($usdPrice, 2) . " USD\n";
                        $message .= "Tasa BCV: " . number_format($latestBcv->rate, 2, ',', '.') . " Bs.\n";
                        $message .= "Tasa Binance: " . number_format($latestBinance->rate, 2, ',', '.') . " Bs.\n";
                        $message .= "Precio BCV: " . number_format($bcvPrice, 2, ',', '.') . " Bs.\n\n";
                        $message .= "Fórmula: $" . number_format($usdPrice, 2) . " × (" . number_format($latestBinance->rate, 2) . " ÷ " . number_format($latestBcv->rate, 2) . ") = " . number_format($bcvPrice, 2, ',', '.') . " Bs.";
                        
                        Notification::make()
                            ->title('Cálculo de Precio BCV')
                            ->body($message)
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error en el Cálculo')
                            ->body('No se pudieron obtener las tasas necesarias para el cálculo.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}