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
        
        // Estadísticas de campañas activas
        $activeCampaigns = \App\Models\ActiveCampaign::count();
        $scheduledCampaigns = \App\Models\ActiveCampaign::where('campaign_start_time', '>', now())->count();
        
        // Estadísticas de conciliaciones
        $totalReconciliations = \App\Models\CampaignPlanReconciliation::count();
        $completedReconciliations = \App\Models\CampaignPlanReconciliation::where('reconciliation_status', 'completed')->count();
        $pausedReconciliations = \App\Models\CampaignPlanReconciliation::where('reconciliation_status', 'paused')->count();
        
        // Calcular métricas adicionales
        $profitMargin = $totalIncome > 0 ? ($totalProfits / $totalIncome) * 100 : 0;
        $averageTransactionValue = $totalTransactions > 0 ? $totalIncome / $totalTransactions : 0;
        $reconciliationRate = $totalReconciliations > 0 ? ($completedReconciliations / $totalReconciliations) * 100 : 0;

        return [
            // Campañas Activas (Datos Reales)
            Stat::make('Campañas Activas', $activeCampaigns)
                ->description('Cargadas en el sistema')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),

            // Factura Total (Ingresos Reales)
            Stat::make('Factura Total', '$' . number_format($totalIncome, 2))
                ->description('Promedio: $' . number_format($averageTransactionValue, 2) . ' por transacción')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            // Gasto Total (Pago a Publicidad)
            Stat::make('Gasto Total', '$' . number_format($totalExpenses, 2))
                ->description('Pago a Meta Ads')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            // Ganancia Total
            Stat::make('Ganancia Total', '$' . number_format($totalProfits, 2))
                ->description('Margen: ' . number_format($profitMargin, 1) . '%')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

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
