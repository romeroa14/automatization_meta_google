<?php

namespace App\Filament\Widgets;

use App\Models\AutomationTask;
use App\Models\FacebookAccount;
use App\Models\GoogleSheet;
use App\Models\TaskLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AutomationStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTasks = AutomationTask::count();
        $activeTasks = AutomationTask::where('is_active', true)->count();
        $totalAccounts = FacebookAccount::count();
        $totalSheets = GoogleSheet::count();
        
        $todayLogs = TaskLog::whereDate('started_at', today());
        $successfulToday = $todayLogs->where('status', 'success')->count();
        $failedToday = $todayLogs->where('status', 'failed')->count();
        $runningNow = TaskLog::where('status', 'running')->count();
        
        $lastExecution = TaskLog::latest('started_at')->first();
        $lastExecutionTime = $lastExecution ? $lastExecution->started_at->diffForHumans() : 'Nunca';
        
        return [
            Stat::make('Tareas Activas', $activeTasks)
                ->description("De {$totalTasks} tareas totales")
                ->descriptionIcon('heroicon-o-play-circle')
                ->color($activeTasks > 0 ? 'success' : 'gray'),
                
            Stat::make('Cuentas Facebook', $totalAccounts)
                ->description('Configuradas')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('blue'),
                
            Stat::make('Google Sheets', $totalSheets)
                ->description('Conectadas')
                ->descriptionIcon('heroicon-o-table-cells')
                ->color('green'),
                
            Stat::make('Ejecuciones Hoy', $successfulToday + $failedToday)
                ->description("{$successfulToday} exitosas, {$failedToday} fallidas")
                ->descriptionIcon('heroicon-o-clock')
                ->color($failedToday > 0 ? 'warning' : 'success'),
                
            Stat::make('Ejecutándose Ahora', $runningNow)
                ->description('Tareas en proceso')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($runningNow > 0 ? 'info' : 'gray'),
                
            Stat::make('Última Ejecución', $lastExecutionTime)
                ->description('Tiempo transcurrido')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('gray'),
        ];
    }
}
