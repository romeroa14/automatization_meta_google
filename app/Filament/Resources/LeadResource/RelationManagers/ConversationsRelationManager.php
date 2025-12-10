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
                Forms\Components\TextInput::make('sender')
                    ->readOnly(),
                Forms\Components\TextInput::make('platform')
                    ->readOnly(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->maxLength(65535)
                    ->readOnly()
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('created_at')
                    ->label('Fecha')
                    ->content(fn (Conversation $record): string => $record->created_at->toDateTimeString()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender')
                    ->label('Remitente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Plataforma')
                    ->badge(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->wrap()
                    ->searchable(),
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
