<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppPhoneNumberResource\Pages;
use App\Filament\Resources\WhatsAppPhoneNumberResource\RelationManagers;
use App\Models\WhatsAppPhoneNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WhatsAppPhoneNumberResource extends Resource
{
    protected static ?string $model = WhatsAppPhoneNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    
    protected static ?string $navigationGroup = 'Multi-Tenant';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $label = 'Número de WhatsApp';
    
    protected static ?string $pluralLabel = 'Números de WhatsApp';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['leads' => function ($query) {
                $query->whereNotNull('whatsapp_phone_number_id');
            }]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Número')
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organización')
                            ->relationship('organization', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Número de Teléfono')
                            ->tel()
                            ->required()
                            ->placeholder('+584241234567')
                            ->helperText('Formato E.164 (ej: +584241234567)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('display_name')
                            ->label('Nombre para Mostrar')
                            ->placeholder('Soporte Principal')
                            ->maxLength(255),
                    ])->columns(3),
                
                Forms\Components\Section::make('Configuración de Meta/WhatsApp')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number_id')
                            ->label('Phone Number ID (Meta)')
                            ->required()
                            ->maxLength(255)
                            ->helperText('ID del número en Meta Business'),
                        Forms\Components\TextInput::make('waba_id')
                            ->label('WABA ID')
                            ->required()
                            ->maxLength(255)
                            ->helperText('WhatsApp Business Account ID'),
                        Forms\Components\Textarea::make('access_token')
                            ->label('Access Token')
                            ->required()
                            ->rows(3)
                            // ->password()
                            ->helperText('Token de acceso (se encripta automáticamente)'),
                        Forms\Components\TextInput::make('verify_token')
                            ->label('Verify Token')
                            ->maxLength(255)
                            ->helperText('Token para verificación de webhooks'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Webhooks y Estado')
                    ->schema([
                        Forms\Components\TextInput::make('webhook_url')
                            ->label('URL del Webhook')
                            ->url()
                            ->placeholder('https://app.admetricas.com/api/webhook/whatsapp')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'active' => 'Activo',
                                'suspended' => 'Suspendido',
                                'inactive' => 'Inactivo',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Select::make('quality_rating')
                            ->label('Calificación de Calidad')
                            ->options([
                                'green' => '🟢 Verde (Excelente)',
                                'yellow' => '🟡 Amarillo (Media)',
                                'red' => '🔴 Rojo (Baja)',
                            ])
                            ->nullable(),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Número Predeterminado')
                            ->helperText('Solo un número puede ser predeterminado por organización'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Organización')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Número')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nombre')
                    ->searchable()
                    ->placeholder('Sin nombre'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'secondary' => 'pending',
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'active' => 'Activo',
                        'suspended' => 'Suspendido',
                        'inactive' => 'Inactivo',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('quality_rating')
                    ->label('Calidad')
                    ->colors([
                        'success' => 'green',
                        'warning' => 'yellow',
                        'danger' => 'red',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'green' => '🟢 Verde',
                        'yellow' => '🟡 Amarillo',
                        'red' => '🔴 Rojo',
                        default => 'N/A',
                    }),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Predeterminado')
                    ->boolean(),
                Tables\Columns\IconColumn::make('verified_at')
                    ->label('Verificado')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->verified_at !== null),
                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Leads')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Último Uso')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('organization')
                    ->label('Organización')
                    ->relationship('organization', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'active' => 'Activo',
                        'suspended' => 'Suspendido',
                        'inactive' => 'Inactivo',
                    ]),
                Tables\Filters\SelectFilter::make('quality_rating')
                    ->label('Calidad')
                    ->options([
                        'green' => '🟢 Verde',
                        'yellow' => '🟡 Amarillo',
                        'red' => '🔴 Rojo',
                    ]),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Predeterminado'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListWhatsAppPhoneNumbers::route('/'),
            'create' => Pages\CreateWhatsAppPhoneNumber::route('/create'),
            'edit' => Pages\EditWhatsAppPhoneNumber::route('/{record}/edit'),
        ];
    }
}
