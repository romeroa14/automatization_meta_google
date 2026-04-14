<?php

namespace App\Filament\Resources\GoogleSheetResource\Pages;

use App\Filament\Resources\GoogleSheetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGoogleSheet extends CreateRecord
{
    protected static string $resource = GoogleSheetResource::class;
}
