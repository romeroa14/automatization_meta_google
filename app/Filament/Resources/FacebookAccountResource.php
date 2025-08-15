<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FacebookAccountResource\Pages;
use App\Filament\Resources\FacebookAccountResource\RelationManagers;
use App\Models\FacebookAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FacebookAccountResource extends Resource
{
    protected static ?string $model = FacebookAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Cuentas Facebook';

    protected static ?string $modelLabel = 'Cuenta Facebook';

    protected static ?string $pluralModelLabel = 'Cuentas Facebook';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Cuenta')
                    ->description('Configura los datos de tu cuenta de Facebook Ads')
                    ->schema([
                        Forms\Components\TextInput::make('account_name')
                            ->label('Nombre de la Cuenta')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Mi Cuenta de Facebook Ads'),
                        Forms\Components\TextInput::make('account_id')
                            ->label('ID de la Cuenta')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('123456789')
                            ->helperText('El ID de tu cuenta de Facebook Ads Manager'),
                    ])->columns(2),

                Forms\Components\Section::make('Credenciales de la API')
                    ->description('Configura las credenciales de la API de Facebook')
                    ->schema([
                        Forms\Components\TextInput::make('app_id')
                            ->label('App ID')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('123456789012345'),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->required()
                            ->maxLength(255)
                            ->password()
                            ->placeholder('abcdef123456789...'),
                        Forms\Components\Textarea::make('access_token')
                            ->label('Access Token')
                            ->required()
                            ->rows(3)
                            ->placeholder('EAAG...')
                            ->helperText('El token de acceso de tu aplicación de Facebook'),
                    ])->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Activa o desactiva esta configuración'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Nombre de la Cuenta')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('account_id')
                    ->label('ID de Cuenta')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copiado al portapapeles'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacebookAccounts::route('/'),
            'create' => Pages\CreateFacebookAccount::route('/create'),
            'edit' => Pages\EditFacebookAccount::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
