<?php

namespace App\Filament\Resources\AccountingTransactionResource\Pages;

use App\Filament\Resources\AccountingTransactionResource;
use App\Services\MicroscopicAccountingService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListAccountingTransactions extends ListRecords
{
    protected static string $resource = AccountingTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('microscopic_accounting')
                ->label('Contabilidad Microscópica')
                ->icon('heroicon-o-magnifying-glass')
                ->color('warning')
                ->action(function () {
                    $service = new MicroscopicAccountingService();
                    $results = $service->processCampaignsByStatus();
                    
                    $summary = $results['summary'] ?? [];
                    $message = "📊 Procesadas: {$summary['total_campaigns_processed']} campañas\n";
                    $message .= "✅ Conciliadas: {$summary['total_campaigns_reconciled']} campañas\n";
                    $message .= "❌ Errores: {$summary['total_errors']}\n";
                    $message .= "📈 Tasa de éxito: " . number_format($summary['success_rate'], 2) . "%\n\n";
                    
                    $message .= "📋 Por estado:\n";
                    foreach ($summary['status_breakdown'] as $status => $count) {
                        $emoji = match($status) {
                            'active' => '🟢',
                            'paused' => '🔴',
                            'scheduled' => '🔵',
                            'completed' => '✅',
                            default => '❓'
                        };
                        $message .= "{$emoji} " . strtoupper($status) . ": {$count}\n";
                    }
                    
                    Notification::make()
                        ->title('Contabilidad Microscópica Completada')
                        ->body($message)
                        ->success()
                        ->send();
                        
                    // Refrescar la página para mostrar los nuevos datos
                    $this->redirect(request()->url());
                }),
                
            Actions\CreateAction::make(),
        ];
    }
}
