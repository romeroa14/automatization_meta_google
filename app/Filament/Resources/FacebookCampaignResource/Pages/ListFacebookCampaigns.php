<?php

namespace App\Filament\Resources\FacebookCampaignResource\Pages;

use App\Filament\Resources\FacebookCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFacebookCampaigns extends ListRecords
{
    protected static string $resource = FacebookCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
