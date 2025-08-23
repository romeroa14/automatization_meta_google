<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí puedes agregar widgets si los necesitas
        ];
    }

    public function getTitle(): string
    {
        return 'Reportes';
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Aquí puedes agregar widgets si los necesitas
        ];
    }

    public function mount(): void
    {
        parent::mount();
    }
}
