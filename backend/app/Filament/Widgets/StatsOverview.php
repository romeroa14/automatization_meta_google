<?php

namespace App\Filament\Widgets;

use App\Models\AutomationTask;
use App\Models\FacebookAccount;
use App\Models\GoogleSheet;
use App\Models\TaskLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Cuentas Facebook', FacebookAccount::count())
                ->description('Total de cuentas configuradas')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('Google Sheets', GoogleSheet::count())
                ->description('Total de spreadsheets configurados')
                ->descriptionIcon('heroicon-o-table-cells')
                ->color('success'),

            Stat::make('Tareas Activas', AutomationTask::active()->count())
                ->description('Tareas de automatización activas')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->color('warning'),

            Stat::make('Ejecuciones Exitosas', TaskLog::success()->count())
                ->description('Total de sincronizaciones exitosas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Ejecuciones Fallidas', TaskLog::error()->count())
                ->description('Total de sincronizaciones fallidas')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Última Ejecución', function () {
                $lastLog = TaskLog::latest()->first();
                return $lastLog ? $lastLog->started_at->diffForHumans() : 'Nunca';
            })
                ->description('Tiempo desde la última sincronización')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),
        ];
    }
}
