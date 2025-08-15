<?php

namespace App\Filament\Resources\FacebookAccountResource\Pages;

use App\Filament\Resources\FacebookAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacebookAccount extends EditRecord
{
    protected static string $resource = FacebookAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
