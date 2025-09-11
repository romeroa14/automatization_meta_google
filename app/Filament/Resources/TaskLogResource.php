<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskLogResource\Pages;
use App\Filament\Resources\TaskLogResource\RelationManagers;
use App\Models\TaskLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskLogResource extends Resource
{
    protected static ?string $model = TaskLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Logs de Tareas';

    protected static ?string $modelLabel = 'Log de Tarea';

    protected static ?string $pluralModelLabel = 'Logs de Tareas';

    protected static ?string $navigationGroup = 'Automatizaciones';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Log')
                    ->schema([
                        Forms\Components\Select::make('automation_task_id')
                            ->label('Tarea de Automatización')
                            ->relationship('automationTask', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'running' => 'Ejecutando',
                                'success' => 'Exitoso',
                                'failed' => 'Fallido',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje')
                            ->rows(3),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Mensaje de Error')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Métricas')
                    ->schema([
                        Forms\Components\TextInput::make('records_processed')
                            ->label('Registros Procesados')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('execution_time')
                            ->label('Tiempo de Ejecución (segundos)')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Fechas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Iniciado')
                            ->required(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completado'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('automationTask.name')
                    ->label('Tarea')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'success',
                        'failed' => 'danger',
                        'running' => 'warning',
                    ]),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->placeholder('En progreso...'),
                Tables\Columns\TextColumn::make('records_processed')
                    ->label('Registros')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('execution_time')
                    ->label('Tiempo')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . 's')
                    ->sortable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'running' => 'Ejecutando',
                        'success' => 'Exitoso',
                        'failed' => 'Fallido',
                    ]),
                Tables\Filters\Filter::make('created_today')
                    ->label('Creados hoy')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ])
            ->defaultSort('started_at', 'desc');
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
            'index' => Pages\ListTaskLogs::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
