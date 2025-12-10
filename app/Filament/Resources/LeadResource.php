<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre Completo')
                    ->readOnly(),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->readOnly(),
                Forms\Components\TextInput::make('intention')
                    ->label('Intención')
                    ->readOnly(),
                Forms\Components\TextInput::make('stage')
                    ->label('Etapa')
                    ->readOnly(),
                Forms\Components\TextInput::make('confidence')
                    ->label('Confianza')
                    ->numeric()
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('intention')
                    ->label('Intención')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compra' => 'success',
                        'consulta' => 'info',
                        'reclamo' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('stage')
                    ->label('Etapa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nuevo' => 'gray',
                        'contactado' => 'warning',
                        'interesado' => 'info',
                        'cliente' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('confidence')
                    ->label('Confianza')
                    ->numeric()
                    ->sortable()
                    ->description(fn (Lead $record): string => $record->confidence . '%')
                    ->color(fn (string $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('Filtrar por Etapa')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'contactado' => 'Contactado',
                        'interesado' => 'Interesado',
                        'cliente' => 'Cliente',
                    ]),
                Tables\Filters\SelectFilter::make('intention')
                    ->label('Filtrar por Intención')
                    ->options([
                        'compra' => 'Compra',
                        'consulta' => 'Consulta',
                        'reclamo' => 'Reclamo',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ConversationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
