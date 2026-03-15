<?php

namespace App\Filament\Resources\GoogleSheetResource\Pages;

use App\Filament\Resources\GoogleSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoogleSheets extends ListRecords
{
    protected static string $resource = GoogleSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
