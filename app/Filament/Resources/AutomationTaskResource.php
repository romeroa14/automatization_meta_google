<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutomationTaskResource\Pages;
use App\Filament\Resources\AutomationTaskResource\RelationManagers;
use App\Models\AutomationTask;
use App\Models\FacebookAccount;
use App\Models\GoogleSheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Jobs\SyncFacebookAdsToGoogleSheets;
use Filament\Notifications\Notification;

class AutomationTaskResource extends Resource
{
    protected static ?string $model = AutomationTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Tareas de Automatización';

    protected static ?string $modelLabel = 'Tarea de Automatización';

    protected static ?string $pluralModelLabel = 'Tareas de Automatización';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Tarea')
                    ->description('Configura los datos básicos de la tarea de automatización')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Tarea')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Sincronización Diaria de Métricas'),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Descripción opcional de la tarea...'),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración de Conexiones')
                    ->description('Selecciona las cuentas de Facebook y Google Sheets')
                    ->schema([
                        Forms\Components\Select::make('facebook_account_id')
                            ->label('Cuenta de Facebook')
                            ->options(FacebookAccount::active()->pluck('account_name', 'id'))
                            ->required()
                            ->searchable()
                            ->placeholder('Selecciona una cuenta de Facebook'),
                        Forms\Components\Select::make('google_sheet_id')
                            ->label('Google Sheet')
                            ->options(GoogleSheet::active()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->placeholder('Selecciona un Google Sheet'),
                    ])->columns(2),

                Forms\Components\Section::make('Programación')
                    ->description('Configura cuándo se ejecutará la tarea')
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->label('Frecuencia')
                            ->options([
                                'hourly' => 'Cada hora',
                                'daily' => 'Diario',
                                'weekly' => 'Semanal',
                                'monthly' => 'Mensual',
                                'custom' => 'Personalizado',
                            ])
                            ->required()
                            ->default('daily'),
                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label('Hora Programada')
                            ->helperText('Hora específica para ejecutar la tarea (opcional)'),
                    ])->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Activa o desactiva esta tarea'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('facebookAccount.account_name')
                    ->label('Cuenta Facebook')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('googleSheet.name')
                    ->label('Google Sheet')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frecuencia')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hourly' => 'info',
                        'daily' => 'success',
                        'weekly' => 'warning',
                        'monthly' => 'danger',
                        'custom' => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('last_run')
                    ->label('Última Ejecución')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca'),
                Tables\Columns\TextColumn::make('next_run')
                    ->label('Próxima Ejecución')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('No programado'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                Tables\Filters\SelectFilter::make('frequency')
                    ->label('Frecuencia')
                    ->options([
                        'hourly' => 'Cada hora',
                        'daily' => 'Diario',
                        'weekly' => 'Semanal',
                        'monthly' => 'Mensual',
                        'custom' => 'Personalizado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('run_now')
                    ->label('Ejecutar Ahora')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Ejecutar Tarea de Sincronización')
                    ->modalSubmitActionLabel('Sí, Ejecutar')
                    ->action(function (AutomationTask $record) {
                        try {
                            // Despachar el job de sincronización
                            SyncFacebookAdsToGoogleSheets::dispatch($record);
                            
                            Notification::make()
                                ->title('Tarea Despachada')
                                ->body("La tarea '{$record->name}' ha sido enviada a la cola de procesamiento.")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body("Error al ejecutar la tarea: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalDescription(fn (AutomationTask $record) => "¿Estás seguro de que quieres ejecutar la tarea '{$record->name}' ahora?"),
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
            'index' => Pages\ListAutomationTasks::route('/'),
            'create' => Pages\CreateAutomationTask::route('/create'),
            'edit' => Pages\EditAutomationTask::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
