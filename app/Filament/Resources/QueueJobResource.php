<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QueueJobResource\Pages;
use App\Models\QueueJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class QueueJobResource extends Resource
{
    protected static ?string $model = QueueJob::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Automatizaciones';

    protected static ?string $navigationLabel = 'Colas de Trabajo';

    protected static ?string $modelLabel = 'Job en Cola';

    protected static ?string $pluralModelLabel = 'Jobs en Cola';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Job')
                    ->schema([
                        Forms\Components\TextInput::make('job_name')
                            ->label('Nombre del Job')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('queue')
                            ->label('Cola')
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => '⏳ Pendiente',
                                'processing' => '🔄 Procesando',
                                'completed' => '✅ Completado',
                                'failed' => '❌ Fallido',
                            ])
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('attempts')
                            ->label('Intentos')
                            ->disabled(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Tiempos')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Creado')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('available_at')
                            ->label('Disponible desde')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Iniciado')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completado')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('execution_time')
                            ->label('Tiempo de Ejecución (segundos)')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('age')
                            ->label('Edad')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
                
                Forms\Components\Section::make('Detalles')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label('Mensaje de Error')
                            ->disabled()
                            ->rows(3),
                        
                        Forms\Components\KeyValue::make('job_data')
                            ->label('Datos del Job')
                            ->disabled(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('job_name')
                    ->label('Job')
                    ->searchable()
                   
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('queue')
                    ->label('Cola')
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => '⏳ Pendiente',
                        'processing' => '🔄 Procesando',
                        'completed' => '✅ Completado',
                        'failed' => '❌ Fallido',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('attempts')
                    ->label('Intentos')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('age')
                    ->label('Edad')
                  
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('created_at', $direction);
                    }),
                
                Tables\Columns\TextColumn::make('execution_time')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}s" : '-')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(30),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => '⏳ Pendiente',
                        'processing' => '🔄 Procesando',
                        'completed' => '✅ Completado',
                        'failed' => '❌ Fallido',
                    ]),
                
                SelectFilter::make('queue')
                    ->label('Cola')
                    ->options(fn () => QueueJob::distinct()->pluck('queue', 'queue')->toArray()),
                
                Filter::make('recent')
                    ->label('Últimas 24 horas')
                    ->query(fn (Builder $query): Builder => $query->recent(24)),
                
                Filter::make('delayed')
                    ->label('Jobs Atrasados')
                    ->query(fn (Builder $query): Builder => $query->where('available_at', '<', now())->where('status', 'pending')),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('retry')
                    ->label('Reintentar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'failed')
                    ->requiresConfirmation()
                    ->action(function (QueueJob $record) {
                        // Aquí podrías implementar la lógica para reintentar el job
                        $record->update([
                            'status' => 'pending',
                            'attempts' => 0,
                            'error_message' => null,
                            'started_at' => null,
                            'completed_at' => null,
                        ]);
                    }),
                
                Tables\Actions\Action::make('delete')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (QueueJob $record) => $record->delete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Actualizar cada 30 segundos
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
            'index' => Pages\ListQueueJobs::route('/'),
            'view' => Pages\ViewQueueJob::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = QueueJob::pending()->count();
        $failedCount = QueueJob::failed()->count();
        
        if ($failedCount > 0) {
            return "{$pendingCount}/{$failedCount}";
        }
        
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $failedCount = QueueJob::failed()->count();
        return $failedCount > 0 ? 'danger' : 'warning';
    }
}
