<?php

namespace App\Filament\Widgets;

use App\Models\ExchangeRate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExchangeRateStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;
    
    protected static ?string $pollingInterval = '30s';
    
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
        
        // Tasa BCV con indicador de tendencia
        if ($latestBcv) {
            $previousBcv = ExchangeRate::where('source', 'BCV')
                ->where('currency_code', 'USD')
                ->where('is_valid', true)
                ->where('fetched_at', '<', $latestBcv->fetched_at)
                ->orderBy('fetched_at', 'desc')
                ->first();
                
            $trend = $previousBcv ? ($latestBcv->rate > $previousBcv->rate ? 'up' : 'down') : 'stable';
            $trendIcon = match($trend) {
                'up' => 'heroicon-m-arrow-trending-up',
                'down' => 'heroicon-m-arrow-trending-down',
                default => 'heroicon-m-minus'
            };
            $trendColor = match($trend) {
                'up' => 'success',
                'down' => 'danger',
                default => 'gray'
            };
            
            $stats[] = Stat::make('BCV (USD)', number_format($latestBcv->rate, 2, ',', '.') . ' Bs.')
                ->description('Banco Central de Venezuela')
                ->descriptionIcon($trendIcon)
                ->color($trendColor)
                ->chart($this->getBcvChartData());
        }
        
        // Tasa Binance con indicador de tendencia
        if ($latestBinance) {
            $previousBinance = ExchangeRate::where('source', 'BINANCE')
                ->where('currency_code', 'USD')
                ->where('is_valid', true)
                ->where('fetched_at', '<', $latestBinance->fetched_at)
                ->orderBy('fetched_at', 'desc')
                ->first();
                
            $trend = $previousBinance ? ($latestBinance->rate > $previousBinance->rate ? 'up' : 'down') : 'stable';
            $trendIcon = match($trend) {
                'up' => 'heroicon-m-arrow-trending-up',
                'down' => 'heroicon-m-arrow-trending-down',
                default => 'heroicon-m-minus'
            };
            $trendColor = match($trend) {
                'up' => 'success',
                'down' => 'danger',
                default => 'gray'
            };
            
            $stats[] = Stat::make('Binance (USD)', number_format($latestBinance->rate, 2, ',', '.') . ' Bs.')
                ->description('Exchange Monitor')
                ->descriptionIcon($trendIcon)
                ->color($trendColor)
                ->chart($this->getBinanceChartData());
        }
        
        // Diferencia entre tasas
        if ($latestBcv && $latestBinance) {
            $difference = $latestBinance->rate - $latestBcv->rate;
            $percentageDiff = ($difference / $latestBcv->rate) * 100;
            
            $stats[] = Stat::make('Diferencia', number_format($difference, 2, ',', '.') . ' Bs.')
                ->description(number_format($percentageDiff, 1) . '% más alto en Binance')
                ->descriptionIcon('heroicon-m-scale')
                ->color($percentageDiff > 50 ? 'danger' : ($percentageDiff > 40 ? 'warning' : 'info'));
        }
        
        // Factor de conversión
        if ($latestBcv && $latestBinance) {
            $conversionFactor = $latestBinance->rate / $latestBcv->rate;
            
            $stats[] = Stat::make('Factor de Conversión', number_format($conversionFactor, 3) . 'x')
                ->description('Multiplicador para precio BCV')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary');
        }
        
        return $stats;
    }
    
    private function getBcvChartData(): array
    {
        $rates = ExchangeRate::where('source', 'BCV')
            ->where('currency_code', 'USD')
            ->where('is_valid', true)
            ->where('fetched_at', '>=', now()->subHours(24))
            ->orderBy('fetched_at')
            ->pluck('rate')
            ->toArray();
            
        // Si no hay suficientes datos, generar datos de ejemplo
        if (count($rates) < 2) {
            return [156.37, 156.40, 156.35, 156.38, 156.37, 156.39, 156.36, 156.37];
        }
        
        return $rates;
    }
    
    private function getBinanceChartData(): array
    {
        $rates = ExchangeRate::where('source', 'BINANCE')
            ->where('currency_code', 'USD')
            ->where('is_valid', true)
            ->where('fetched_at', '>=', now()->subHours(24))
            ->orderBy('fetched_at')
            ->pluck('rate')
            ->toArray();
            
        // Si no hay suficientes datos, generar datos de ejemplo
        if (count($rates) < 2) {
            return [234.98, 235.20, 234.75, 235.10, 234.95, 235.15, 234.85, 234.98];
        }
        
        return $rates;
    }
}
