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
        // Estadísticas de planes de publicidad
        $totalPlans = AdvertisingPlan::count();
        $activePlans = AdvertisingPlan::active()->count();
        $totalRevenue = AdvertisingPlan::sum('client_price');
        $totalProfit = AdvertisingPlan::sum('profit_margin');

        // Estadísticas de conciliaciones
        $totalReconciliations = CampaignReconciliation::count();
        $activeReconciliations = CampaignReconciliation::active()->count();
        $pendingReconciliations = CampaignReconciliation::pending()->count();
        $completedReconciliations = CampaignReconciliation::completed()->count();

        // Estadísticas de transacciones
        $totalTransactions = AccountingTransaction::count();
        $totalIncome = AccountingTransaction::income()->completed()->sum('amount');
        $totalExpenses = AccountingTransaction::expense()->completed()->sum('amount');
        $totalProfits = AccountingTransaction::profit()->completed()->sum('amount');

        // Estadísticas de cuentas de Facebook
        $totalFacebookAccounts = FacebookAccount::count();
        $activeFacebookAccounts = FacebookAccount::where('is_active', true)->count();

        // Calcular métricas adicionales
        $averagePlanPrice = $totalPlans > 0 ? $totalRevenue / $totalPlans : 0;
        $averageProfitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
        $reconciliationRate = $totalReconciliations > 0 ? ($completedReconciliations / $totalReconciliations) * 100 : 0;

        return [
            // Planes de Publicidad
            Stat::make('Planes Activos', $activePlans)
                ->description('De ' . $totalPlans . ' totales')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Ingresos Totales', '$' . number_format($totalRevenue, 2))
                ->description('Precio promedio: $' . number_format($averagePlanPrice, 2))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Ganancia Total', '$' . number_format($totalProfit, 2))
                ->description('Margen: ' . number_format($averageProfitMargin, 1) . '%')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            // Conciliaciones de Campañas
            Stat::make('Campañas Activas', $activeReconciliations)
                ->description('De ' . $totalReconciliations . ' totales')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Campañas Pendientes', $pendingReconciliations)
                ->description('Tasa de conciliación: ' . number_format($reconciliationRate, 1) . '%')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Campañas Completadas', $completedReconciliations)
                ->description('Finalizadas exitosamente')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            // Transacciones Contables
            Stat::make('Ingresos Reales', '$' . number_format($totalIncome, 2))
                ->description('De ' . $totalTransactions . ' transacciones')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gastos Reales', '$' . number_format($totalExpenses, 2))
                ->description('En Meta Ads')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Ganancias Reales', '$' . number_format($totalProfits, 2))
                ->description('Neto del negocio')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            // Cuentas de Facebook
            Stat::make('Cuentas Facebook', $activeFacebookAccounts)
                ->description('De ' . $totalFacebookAccounts . ' totales')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
        ];
    }
}
