<?php

namespace App\Filament\Resources\ClientPortal;

use App\Filament\Resources\ClientPortal\Pages;
use App\Models\Tenant;
use Filament\Resources\Resource;
use Filament\Resources\Pages\Page;

class ClientPortalResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'ads_vnzla';

    protected static ?string $slug = 'client/ads-vnzla';

    public static function getPages(): array
    {
        return [
            'dashboard' => Pages\Dashboard::route('/dashboard'),
            'conversations' => Pages\Conversations::route('/conversations'),
            'leads' => Pages\Leads::route('/leads'),
            'chatbot-config' => Pages\ChatbotConfig::route('/chatbot-config'),
            'documents' => Pages\Documents::route('/documents'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}