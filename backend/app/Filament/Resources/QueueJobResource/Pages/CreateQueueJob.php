<?php

namespace App\Filament\Resources\QueueJobResource\Pages;

use App\Filament\Resources\QueueJobResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateQueueJob extends CreateRecord
{
    protected static string $resource = QueueJobResource::class;
}
