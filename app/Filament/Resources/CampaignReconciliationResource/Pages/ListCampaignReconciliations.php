<?php

namespace App\Filament\Resources\CampaignReconciliationResource\Pages;

use App\Filament\Resources\CampaignReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampaignReconciliations extends ListRecords
{
    protected static string $resource = CampaignReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('bulk_detect_and_reconcile')
                ->label('🚀 DETECCIÓN MASIVA AUTOMÁTICA')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('🚀 Detección Masiva de Campañas')
                ->modalDescription('Esta acción detectará automáticamente todas las campañas activas de Meta Ads y las conciliará con tus planes de publicidad. ¿Continuar?')
                ->modalSubmitActionLabel('¡SÍ, DETECTAR MASIVAMENTE!')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    try {
                        // Aquí iría la lógica de detección masiva
                        // Por ahora solo notificamos
                        \Filament\Notifications\Notification::make()
                            ->title('🚀 Detección Masiva Iniciada')
                            ->body('Esta funcionalidad estará disponible en la próxima versión.')
                            ->info()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Error')
                            ->body('Error en la detección masiva: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
