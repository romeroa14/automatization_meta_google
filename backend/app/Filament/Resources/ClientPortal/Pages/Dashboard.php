<?php

namespace App\Filament\Resources\ClientPortal\Pages;

use App\Models\Tenant;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Lead;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string $view = 'filament.resources.client-portal.pages.dashboard';

    public $tenant;
    public $stats = [];
    public $recentConversations = [];
    public $recentLeads = [];

    public function mount(): void
    {
        // Get current tenant (ads_vnzla for now, should from auth)
        $this->tenant = Tenant::findBySlug('ads_vnzla');
        
        if (!$this->tenant) {
            abort(404, 'Tenant no encontrado');
        }

        // Calculate stats
        $this->stats = [
            'conversations_total' => Conversation::where('tenant_id', $this->tenant->id)->count(),
            'conversations_active' => Conversation::where('tenant_id', $this->tenant->id)
                ->where('status', 'active')->count(),
            'messages_today' => ConversationMessage::whereHas('conversation', function ($q) {
                $q->where('tenant_id', $this->tenant->id);
            })->whereDate('created_at', today())->count(),
            'leads_new' => Lead::where('tenant_id', $this->tenant->id)
                ->where('stage', 'nuevo')->count(),
        ];

        // Recent conversations
        $this->recentConversations = Conversation::where('tenant_id', $this->tenant->id)
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->latest('last_message_at')
            ->limit(10)
            ->get();

        // Recent leads
        $this->recentLeads = Lead::where('tenant_id', $this->tenant->id)
            ->latest()
            ->limit(10)
            ->get();
    }
}