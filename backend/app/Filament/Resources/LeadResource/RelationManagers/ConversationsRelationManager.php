<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ConversationsRelationManager extends RelationManager
{
    protected static string $relationship = 'conversations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message_text')
                    ->label('Mensaje Original')
                    ->readOnly()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('response')
                    ->label('Respuesta del Sistema')
                    ->readOnly()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('platform')
                    ->readOnly(),
                Forms\Components\Placeholder::make('created_at')
                    ->label('Fecha')
                    ->content(fn (Conversation $record): string => $record->created_at->toDateTimeString()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message_text')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_client_message')
                    ->label('Origen')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'gray' : 'primary')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Cliente' : 'Sistema'),
                Tables\Columns\TextColumn::make('message_text')
                    ->label('Mensaje')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('response')
                    ->label('Respuesta')
                    ->wrap()
                    ->color('gray')
                    ->searchable()
                    ->visible(fn ($record) => !empty($record->response)),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Plataforma')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
