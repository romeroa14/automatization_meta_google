<?php

namespace App\Filament\Widgets;

use App\Models\ExchangeRate;
use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;

class ExchangeRateInfoWidget extends Widget implements HasActions
{
    use InteractsWithActions, InteractsWithForms;

    protected static string $view = 'filament.widgets.exchange-rate-info-widget';
    
    protected static ?int $sort = 3;
    
    protected static ?string $pollingInterval = '30s';
    
    protected static bool $isLazy = false;
    
    // Solo mostrar en la página de tasas, no en el dashboard principal
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.exchange-rates-page');
    }

    public function getViewData(): array
    {
        $latestBcv = ExchangeRate::getLatestRate('USD', 'BCV');
        $latestBinance = ExchangeRate::getLatestRate('USD', 'BINANCE');
        
        $data = [
            'bcv' => $latestBcv,
            'binance' => $latestBinance,
            'comparison' => null,
            'examples' => []
        ];
        
        if ($latestBcv && $latestBinance) {
            $difference = $latestBinance->rate - $latestBcv->rate;
            $percentageDiff = ($difference / $latestBcv->rate) * 100;
            
            $data['comparison'] = [
                'difference' => $difference,
                'percentage' => $percentageDiff,
                'conversion_factor' => $latestBinance->rate / $latestBcv->rate
            ];
            
            // Ejemplos de cálculo
            $examples = [1, 5, 10, 25, 50, 100];
            foreach ($examples as $usdAmount) {
                $bcvPrice = ExchangeRate::calculateBcvPriceFromBinance($usdAmount);
                if ($bcvPrice) {
                    $data['examples'][] = [
                        'usd' => $usdAmount,
                        'bcv_price' => $bcvPrice,
                        'ves_direct' => $usdAmount * $latestBcv->rate
                    ];
                }
            }
        }
        
        return $data;
    }

    public function refreshAction(): Action
    {
        return Action::make('refresh')
            ->label('Actualizar')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->action(function () {
                try {
                    \Illuminate\Support\Facades\Artisan::call('exchange:update-all', ['currency' => 'USD']);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Tasas Actualizadas')
                        ->body('Las tasas se han actualizado exitosamente.')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Error')
                        ->body('Error al actualizar: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
