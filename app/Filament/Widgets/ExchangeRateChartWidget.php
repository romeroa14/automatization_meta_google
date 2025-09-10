<?php

namespace App\Filament\Widgets;

use App\Models\ExchangeRate;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ExchangeRateChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Evolución de Tasas de Cambio (Últimas 24 horas)';
    
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '60s';
    
    protected static bool $isLazy = false;
    
    // Solo mostrar en la página de tasas, no en el dashboard principal
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.exchange-rates-page');
    }

    protected function getData(): array
    {
        // Obtener datos de las últimas 24 horas
        $startTime = now()->subDay();
        
        $bcvData = ExchangeRate::where('source', 'BCV')
            ->where('currency_code', 'USD')
            ->where('is_valid', true)
            ->where('fetched_at', '>=', $startTime)
            ->orderBy('fetched_at')
            ->get();
            
        $binanceData = ExchangeRate::where('source', 'BINANCE')
            ->where('currency_code', 'USD')
            ->where('is_valid', true)
            ->where('fetched_at', '>=', $startTime)
            ->orderBy('fetched_at')
            ->get();

        // Preparar datos para el gráfico
        $labels = [];
        $bcvRates = [];
        $binanceRates = [];
        
        // Combinar y ordenar todos los timestamps
        $allTimestamps = collect()
            ->merge($bcvData->pluck('fetched_at'))
            ->merge($binanceData->pluck('fetched_at'))
            ->unique()
            ->sort()
            ->values();
        
        foreach ($allTimestamps as $timestamp) {
            $labels[] = $timestamp->format('H:i');
            
            // Buscar tasa BCV para este timestamp
            $bcvRate = $bcvData->where('fetched_at', $timestamp)->first();
            $bcvRates[] = $bcvRate ? $bcvRate->rate : null;
            
            // Buscar tasa Binance para este timestamp
            $binanceRate = $binanceData->where('fetched_at', $timestamp)->first();
            $binanceRates[] = $binanceRate ? $binanceRate->rate : null;
        }

        return [
            'datasets' => [
                [
                    'label' => 'BCV (USD)',
                    'data' => $bcvRates,
                    'borderColor' => 'rgb(59, 130, 246)', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => false,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Binance (USD)',
                    'data' => $binanceRates,
                    'borderColor' => 'rgb(34, 197, 94)', // Green
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'title' => [
                        'display' => true,
                        'text' => 'Tasa (Bs.)'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Hora'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": " + context.parsed.y.toFixed(2) + " Bs.";
                        }'
                    ]
                ]
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false
            ]
        ];
    }
}
