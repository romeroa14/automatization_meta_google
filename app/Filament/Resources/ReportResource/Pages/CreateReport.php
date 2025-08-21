<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateReport extends CreateRecord
{
    protected static string $resource = ReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        
        Notification::make()
            ->title('Reporte Creado')
            ->body("El reporte '{$record->name}' ha sido creado exitosamente.")
            ->success()
            ->send();
    }
}
