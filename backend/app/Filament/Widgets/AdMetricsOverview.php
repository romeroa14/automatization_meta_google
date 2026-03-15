<?php

namespace App\Filament\Widgets;

use App\Models\AdvertisingPlan;
use App\Models\CampaignReconciliation;
use App\Models\AccountingTransaction;
use App\Models\FacebookAccount;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdMetricsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getHeading(): string
    {
        return 'ADMETRICAS.COM - Resumen General';
    }

    protected function getStats(): array
    {
        // Estadísticas REALES de transacciones contables
        $totalTransactions = AccountingTransaction::count();
        $totalIncome = AccountingTransaction::sum('income');
        $totalExpenses = AccountingTransaction::sum('expense');
        $totalProfits = AccountingTransaction::sum('profit');
        
        // Calcular valores reales en Binance
        $totalRealProfitBinance = 0;
        $totalRealIncomeBinance = 0; // Factura total real en Binance
        $totalRealExpenseBinance = 0; // Gasto total real en Binance
        
        // Obtener todas las transacciones
        $allTransactions = AccountingTransaction::all();
        
        foreach ($allTransactions as $transaction) {
            $paidInBinanceRate = $transaction->metadata['paid_in_binance_rate'] ?? false;
            
            if ($paidInBinanceRate) {
                // Si pagó en Binance, los valores reales son los mismos que los tradicionales
                $totalRealProfitBinance += $transaction->profit;
                $totalRealIncomeBinance += $transaction->income;
                $totalRealExpenseBinance += $transaction->expense;
            } else {
                // Si pagó en BCV, aplicar conversión matemática
                $realProfit = \App\Models\ExchangeRate::calculateRealProfitInUsd($transaction->income, $transaction->expense);
                $totalRealProfitBinance += $realProfit ?? $transaction->profit;
                
                // Calcular ingreso real en Binance (lo que realmente recibes)
                $completeEquivalents = \App\Models\ExchangeRate::calculateCompletePlanEquivalents($transaction->expense, $transaction->income);
                $realIncome = $completeEquivalents['real_profit']['real_usd_received'] ?? $transaction->income;
                $totalRealIncomeBinance += $realIncome;
                
                // El gasto siempre es el mismo (USD que pagas a Meta)
                $totalRealExpenseBinance += $transaction->expense;
            }
        }
        
        // Estadísticas de campañas activas
        $activeCampaigns = \App\Models\ActiveCampaign::count();
        $scheduledCampaigns = \App\Models\ActiveCampaign::where('campaign_start_time', '>', now())->count();
        
        // Estadísticas de conciliaciones
        $totalReconciliations = \App\Models\CampaignPlanReconciliation::count();
        $completedReconciliations = \App\Models\CampaignPlanReconciliation::where('reconciliation_status', 'completed')->count();
        $pausedReconciliations = \App\Models\CampaignPlanReconciliation::where('reconciliation_status', 'paused')->count();
        
        // Calcular métricas adicionales usando valores reales en Binance
        $profitMargin = $totalRealIncomeBinance > 0 ? ($totalRealProfitBinance / $totalRealIncomeBinance) * 100 : 0;
        $averageTransactionValue = $totalTransactions > 0 ? $totalRealIncomeBinance / $totalTransactions : 0;
        $reconciliationRate = $totalReconciliations > 0 ? ($completedReconciliations / $totalReconciliations) * 100 : 0;

        return [
            // Campañas Activas (Datos Reales)
            Stat::make('Campañas Activas', $activeCampaigns)
                ->description('Cargadas en el sistema')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),

            // Factura Total (Ingresos Reales en Binance)
            Stat::make('Factura Total (Binance)', '$' . number_format($totalRealIncomeBinance, 2))
                ->description('Promedio: $' . number_format($averageTransactionValue, 2) . ' por transacción')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            // Gasto Total (Pago a Meta en Binance)
            Stat::make('Gasto Total (Binance)', '$' . number_format($totalRealExpenseBinance, 2))
                ->description('Pago a Meta Ads')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            // Ganancia Real Binance
            Stat::make('Ganancia Real Binance', '$' . number_format($totalRealProfitBinance, 2))
                ->description('Margen real: ' . number_format($profitMargin, 1) . '%')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            // Diferencia de Conversión
            Stat::make('Diferencia Conversión', '$' . number_format($totalIncome - $totalRealIncomeBinance, 2))
                ->description('Tradicional vs Real Binance')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($totalIncome > $totalRealIncomeBinance ? 'warning' : 'info'),

            // Conciliaciones Completadas
            Stat::make('Conciliaciones', $completedReconciliations)
                ->description('De ' . $totalReconciliations . ' totales (' . number_format($reconciliationRate, 1) . '% completadas)')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            // Campañas Pausadas
            Stat::make('Campañas Pausadas', $pausedReconciliations)
                ->description('Solo gasto real registrado')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('warning'),
        ];
    }
}
