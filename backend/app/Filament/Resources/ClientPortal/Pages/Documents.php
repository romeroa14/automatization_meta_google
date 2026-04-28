<?php

namespace App\Filament\Resources\ClientPortal\Pages;

use App\Models\Tenant;
use Filament\Pages\Page;

class Documents extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Documentos';
    protected static string $view = 'filament.resources.client-portal.pages.documents';

    public $tenant;

    public function mount(): void
    {
        $this->tenant = Tenant::findBySlug('ads_vnzla');
    }
}