<?php

namespace App\Filament\Resources\GoogleSheetResource\Pages;

use App\Filament\Resources\GoogleSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoogleSheet extends EditRecord
{
    protected static string $resource = GoogleSheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
