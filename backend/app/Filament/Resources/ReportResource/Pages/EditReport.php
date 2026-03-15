<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditReport extends EditRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    Notification::make()
                        ->title('Reporte Eliminado')
                        ->body('El reporte ha sido eliminado exitosamente.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        Notification::make()
            ->title('Reporte Actualizado')
            ->body("El reporte '{$record->name}' ha sido actualizado exitosamente.")
            ->success()
            ->send();
    }
}
