<?php

namespace App\Filament\Resources\WhatsAppPhoneNumberResource\Pages;

use App\Filament\Resources\WhatsAppPhoneNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppPhoneNumber extends EditRecord
{
    protected static string $resource = WhatsAppPhoneNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
