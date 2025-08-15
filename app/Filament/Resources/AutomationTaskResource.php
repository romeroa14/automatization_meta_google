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
use Illuminate\Support\Facades\Queue;
use App\Models\QueueJob;

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
                            ->default('daily')
                            ->reactive(),
                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label('Hora del Día')
                            ->helperText('¿A qué hora específica quieres que se ejecute? (ej: 08:00 para las 8 de la mañana)')
                            ->seconds(false)
                            ->reactive(),
                        Forms\Components\DateTimePicker::make('next_run')
                            ->label('Primera Ejecución')
                            ->helperText('¿Cuándo quieres que se ejecute por primera vez? (Si no configuras, se ejecutará en la próxima hora programada)')
                            ->placeholder('Dejar vacío para usar la hora programada')
                            ->displayFormat('d/m/Y H:i')
                            ->native(false)
                            ->afterStateHydrated(function ($state, $record) {
                                // Si no hay next_run configurado, sugerir basado en scheduled_time
                                if (!$state && !$record) {
                                    $suggestedTime = self::calculateSuggestedTimeStatic('daily');
                                    $suggestedDateTime = now()->copy()->setTime($suggestedTime['hour'], $suggestedTime['minute']);
                                    
                                    // Si la hora sugerida ya pasó hoy, sugerir para mañana
                                    if ($suggestedDateTime->isPast()) {
                                        $suggestedDateTime = $suggestedDateTime->addDay();
                                    }
                                    
                                    return $suggestedDateTime;
                                }
                                return $state;
                            }),
                        Forms\Components\Placeholder::make('scheduling_info')
                            ->label('Información de Programación')
                            ->content(function ($record) {
                                if (!$record) return 'Configura la frecuencia y hora para ver la información';
                                
                                $info = "Frecuencia: {$record->frequency}\n";
                                
                                if ($record->scheduled_time) {
                                    $info .= "Hora programada: {$record->scheduled_time->format('H:i')}\n";
                                }
                                
                                if ($record->next_run) {
                                    $info .= "Próxima ejecución: {$record->next_run->format('d/m/Y H:i')}\n";
                                }
                                
                                if ($record->last_run) {
                                    $info .= "Última ejecución: {$record->last_run->format('d/m/Y H:i')}";
                                }
                                
                                return $info;
                            })
                            ->visible(fn ($record) => $record && $record->exists),
                        Forms\Components\Placeholder::make('last_run_info')
                            ->label('Última Ejecución')
                            ->content(fn ($record) => $record && $record->last_run 
                                ? $record->last_run->format('d/m/Y H:i:s') 
                                : 'Nunca ejecutada')
                            ->visible(fn ($record) => $record && $record->exists),
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
                Tables\Actions\Action::make('configure_first_run')
                    ->label('Configurar Primera Ejecución')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Configurar Primera Ejecución')
                    ->modalSubmitActionLabel('Configurar')
                    ->action(function (AutomationTask $record) {
                        // Calcular una hora inteligente basada en la frecuencia
                        $now = now();
                        $suggestedTime = self::calculateSuggestedTimeStatic($record->frequency);
                        
                        // Calcular la primera ejecución
                        $firstRun = $now->copy()->setTime($suggestedTime['hour'], $suggestedTime['minute']);
                        
                        // Si la hora sugerida ya pasó hoy, programar para mañana
                        if ($firstRun->isPast()) {
                            $firstRun = $firstRun->addDay();
                        }
                        
                        // Calcular la próxima ejecución basada en la frecuencia
                        $nextRun = $record->calculateNextRun() ?? $firstRun->copy()->addDay();
                        
                        $record->update([
                            'next_run' => $firstRun,
                            'scheduled_time' => $firstRun->copy()
                        ]);
                        
                        Notification::make()
                            ->title('Primera Ejecución Configurada')
                            ->body("La tarea se ejecutará por primera vez el {$firstRun->format('d/m/Y H:i')} ({$suggestedTime['description']})")
                            ->success()
                            ->send();
                    })
                    ->modalDescription(fn (AutomationTask $record) => "¿Configurar la primera ejecución de '{$record->name}' con una hora inteligente basada en la frecuencia '{$record->frequency}'?")
                    ->visible(fn (AutomationTask $record) => !$record->next_run),
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

    public static function getNavigationBadgeColor(): ?string
    {
        $failedCount = QueueJob::failed()->count();
        return $failedCount > 0 ? 'danger' : 'warning';
    }

    private static function calculateSuggestedTimeStatic(string $frequency): array
    {
        return match($frequency) {
            'hourly' => [
                'hour' => now()->addHour()->hour,
                'minute' => 0,
                'description' => 'Cada hora en punto'
            ],
            'daily' => [
                'hour' => 8, // 8:00 AM - hora de trabajo
                'minute' => 0,
                'description' => 'Diario a las 8:00 AM'
            ],
            'weekly' => [
                'hour' => 9, // 9:00 AM - lunes
                'minute' => 0,
                'description' => 'Semanal los lunes a las 9:00 AM'
            ],
            'monthly' => [
                'hour' => 10, // 10:00 AM - primer día del mes
                'minute' => 0,
                'description' => 'Mensual el primer día a las 10:00 AM'
            ],
            default => [
                'hour' => 9,
                'minute' => 0,
                'description' => 'Diario a las 9:00 AM'
            ],
        };
    }
}
