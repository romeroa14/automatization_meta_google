<?php

namespace App\Filament\Resources\CampaignReconciliationResource\Pages;

use App\Filament\Resources\CampaignReconciliationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampaignReconciliation extends EditRecord
{
    protected static string $resource = CampaignReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
