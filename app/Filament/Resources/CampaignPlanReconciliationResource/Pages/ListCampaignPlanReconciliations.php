<?php

namespace App\Filament\Resources\CampaignPlanReconciliationResource\Pages;

use App\Filament\Resources\CampaignPlanReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampaignPlanReconciliations extends ListRecords
{
    protected static string $resource = CampaignPlanReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
