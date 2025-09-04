<?php

namespace App\Filament\Resources\CampaignPlanReconciliationResource\Pages;

use App\Filament\Resources\CampaignPlanReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampaignPlanReconciliation extends EditRecord
{
    protected static string $resource = CampaignPlanReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
