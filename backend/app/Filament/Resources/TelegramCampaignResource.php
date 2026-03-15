<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramCampaignResource\Pages;
use App\Filament\Resources\TelegramCampaignResource\RelationManagers;
use App\Models\TelegramCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TelegramCampaignResource extends Resource
{
    protected static ?string $model = TelegramCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Automatizaciones';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'Campaña de Telegram';

    protected static ?string $pluralModelLabel = 'Campañas de Telegram';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('telegram_user_id')
                    ->label('Usuario Telegram')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('campaign_name')
                    ->label('Nombre de Campaña')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('objective_name')
                    ->label('Objetivo')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('budget_type_name')
                    ->label('Tipo Presupuesto')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('formatted_budget')
                    ->label('Presupuesto Diario')
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('formatted_date_range')
                    ->label('Rango de Fechas')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('status_badge')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '⏳ Pendiente' => 'warning',
                        '✅ Creada' => 'success',
                        '❌ Error' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('meta_campaign_id')
                    ->label('ID Meta')
                    ->placeholder('N/A')
                    ->copyable()
                    ->copyMessage('ID copiado'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'created' => 'Creada',
                        'failed' => 'Error',
                    ]),

                Tables\Filters\SelectFilter::make('objective')
                    ->label('Objetivo')
                    ->options([
                        'TRAFFIC' => 'Tráfico',
                        'CONVERSIONS' => 'Conversiones',
                        'REACH' => 'Alcance',
                        'BRAND_AWARENESS' => 'Conocimiento de Marca',
                        'VIDEO_VIEWS' => 'Visualizaciones de Video',
                        'LEAD_GENERATION' => 'Generación de Leads',
                        'MESSAGES' => 'Mensajes',
                        'ENGAGEMENT' => 'Interacción',
                        'APP_INSTALLS' => 'Instalaciones de App',
                        'STORE_VISITS' => 'Visitas a Tienda',
                    ]),

                Tables\Filters\SelectFilter::make('budget_type')
                    ->label('Tipo de Presupuesto')
                    ->options([
                        'campaign_daily_budget' => 'Campaña',
                        'adset_daily_budget' => 'Conjunto de Anuncios',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTelegramCampaigns::route('/'),
            'create' => Pages\CreateTelegramCampaign::route('/create'),
            'edit' => Pages\EditTelegramCampaign::route('/{record}/edit'),
        ];
    }
}
