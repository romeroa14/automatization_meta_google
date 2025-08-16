<?php

namespace App\Filament\Resources\FacebookCampaignResource\Pages;

use App\Filament\Resources\FacebookCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFacebookCampaign extends EditRecord
{
    protected static string $resource = FacebookCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
