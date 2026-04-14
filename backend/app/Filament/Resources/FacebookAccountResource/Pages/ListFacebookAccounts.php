<?php

namespace App\Filament\Resources\FacebookAccountResource\Pages;

use App\Filament\Resources\FacebookAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacebookAccounts extends ListRecords
{
    protected static string $resource = FacebookAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
