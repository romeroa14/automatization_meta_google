<?php

namespace App\Filament\Resources\QueueJobResource\Pages;

use App\Filament\Resources\QueueJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQueueJobs extends ListRecords
{
    protected static string $resource = QueueJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
