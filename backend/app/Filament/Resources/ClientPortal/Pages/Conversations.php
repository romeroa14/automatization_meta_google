<?php

namespace App\Filament\Resources\ClientPortal\Pages;

use App\Models\Tenant;
use App\Models\Conversation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\UsesTables;
use Filament\Tables\Table;
use Filament\Pages\Page;

class Conversations extends Page implements HasTable
{
    use Tables\Concerns\UsesTables;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Conversaciones';
    protected static string $view = 'filament.resources.client-portal.pages.conversations';

    public $tenant;

    public function mount(): void
    {
        $this->tenant = Tenant::findBySlug('ads_vnzla');
    }

    public function getTable(Table $table): Table
    {
        return $table
            ->query(
                Conversation::where('tenant_id', $this->tenant->id)
                    ->withCount('messages')
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer_id')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Plataforma'),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Último mensaje')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Mensajes')
                    ->numeric(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activas',
                        'closed' => 'Cerradas',
                    ]),
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'instagram' => 'Instagram',
                        'whatsapp' => 'WhatsApp',
                        'telegram' => 'Telegram',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.client.conversations.view', $record)),
            ])
            ->paginated(20);
    }
}