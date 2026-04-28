<?php

namespace App\Filament\Resources\ClientPortal\Pages;

use App\Models\Tenant;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Lead;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class ConversationDetail extends Page implements HasTable
{
    use Tables\Concerns\UsesTables;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Conversaciones';
    protected static string $view = 'filament.resources.client-portal.pages.conversation-detail';

    public $tenant;
    public $conversation;
    public $messages = [];

    public function mount(int $conversationId): void
    {
        $this->conversation = Conversation::with('messages')
            ->findOrFail($conversationId);
        
        $this->messages = $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Only accessible via detail
    }
}