<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExchangeRateStatsWidget;
use App\Filament\Widgets\ExchangeRateWidget;
use App\Filament\Widgets\ExchangeRateChartWidget;
use App\Filament\Widgets\ExchangeRateInfoWidget;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ExchangeRatesPage extends Page implements HasActions
{
    use InteractsWithActions, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static string $view = 'filament.pages.exchange-rates-page';
    
    protected static ?string $title = 'Tasas de Cambio';
    
    protected static ?string $navigationLabel = 'Tasas de Cambio';
    
    protected static ?string $navigationGroup = 'Finanzas';
    
    protected static ?int $navigationSort = 1;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('update_all_rates')
                ->label('Actualizar Todas las Tasas')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    try {
                        Artisan::call('exchange:update-all', ['currency' => 'USD']);
                        
                        Notification::make()
                            ->title('Tasas Actualizadas')
                            ->body('Todas las tasas de cambio se han actualizado exitosamente.')
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
                
            Action::make('update_bcv_only')
                ->label('Actualizar Solo BCV')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->action(function () {
                    try {
                        Artisan::call('exchange:update-all', ['currency' => 'USD', '--source' => 'BCV']);
                        
                        Notification::make()
                            ->title('Tasa BCV Actualizada')
                            ->body('La tasa del Banco Central de Venezuela se ha actualizado exitosamente.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al Actualizar BCV')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('update_binance_only')
                ->label('Actualizar Solo Binance')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->action(function () {
                    try {
                        Artisan::call('exchange:update-all', ['currency' => 'USD', '--source' => 'BINANCE']);
                        
                        Notification::make()
                            ->title('Tasa Binance Actualizada')
                            ->body('La tasa de Binance se ha actualizado exitosamente.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al Actualizar Binance')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}