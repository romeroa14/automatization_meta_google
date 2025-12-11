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
                Forms\Components\TextInput::make('client_name')
                    ->label('Nombre Completo')
                    ->readOnly(),
                Forms\Components\TextInput::make('phone_number')
                    ->label('Teléfono')
                    ->tel()
                    ->readOnly(),
                Forms\Components\TextInput::make('intent')
                    ->label('Intención')
                    ->readOnly(),
                Forms\Components\TextInput::make('stage')
                    ->label('Etapa')
                    ->readOnly(),
                Forms\Components\TextInput::make('confidence_score')
                    ->label('Confianza')
                    ->numeric()
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Nombre Completo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('intent')
                    ->label('Intención')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compra' => 'success',
                        'consulta' => 'info',
                        'reclamo' => 'danger',
                        'pricing' => 'success', // Added from screenshot
                        'info' => 'info', // Added from screenshot
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
                        'pricing_discussion' => 'warning', // Added
                        'ready_to_buy' => 'success', // Added
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confianza')
                    ->numeric(2)
                    ->sortable()
                    ->description(fn (Lead $record): string => ($record->confidence_score * 100) . '%')
                    ->color(fn (string $state): string => match (true) {
                        $state >= 0.8 => 'success',
                        $state >= 0.5 => 'warning',
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
                        'pricing_discussion' => 'Discusión de Precio',
                        'ready_to_buy' => 'Listo para Comprar',
                    ]),
                Tables\Filters\SelectFilter::make('intent')
                    ->label('Filtrar por Intención')
                    ->options([
                        'compra' => 'Compra',
                        'consulta' => 'Consulta',
                        'reclamo' => 'Reclamo',
                        'pricing' => 'Precios',
                        'info' => 'Información',
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
