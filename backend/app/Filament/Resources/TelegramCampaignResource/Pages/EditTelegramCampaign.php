<?php

namespace App\Filament\Resources\TelegramCampaignResource\Pages;

use App\Filament\Resources\TelegramCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTelegramCampaign extends EditRecord
{
    protected static string $resource = TelegramCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
