<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignPlanReconciliationResource\Pages;
use App\Models\CampaignPlanReconciliation;
use App\Models\ActiveCampaign;
use App\Models\AdvertisingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CampaignPlanReconciliationResource extends Resource
{
    protected static ?string $model = CampaignPlanReconciliation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Conciliaciones Contables';

    protected static ?string $modelLabel = 'Conciliación Contable';

    protected static ?string $pluralModelLabel = 'Conciliaciones Contables';

    protected static ?string $navigationGroup = 'ADMETRICAS.COM';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de Conciliación')
                    ->schema([
                        Select::make('active_campaign_id')
                            ->label('Campaña Activa')
                            ->options(function () {
                                return ActiveCampaign::all()->mapWithKeys(function ($campaign) {
                                    return [$campaign->id => $campaign->meta_campaign_name . ' (ID: ' . $campaign->meta_campaign_id . ')'];
                                });
                            })
                            ->searchable()
                            ->disabled(),

                        Select::make('advertising_plan_id')
                            ->label('Plan de Publicidad')
                            ->options(AdvertisingPlan::all()->pluck('plan_name', 'id'))
                            ->searchable()
                            ->disabled(),

                        Select::make('reconciliation_status')
                            ->label('Estado de Conciliación')
                            ->options([
                                'pending' => 'Pendiente',
                                'approved' => 'Aprobada',
                                'rejected' => 'Rechazada',
                                'completed' => 'Completada',
                            ])
                            ->default('pending')
                            ->required(),

                        DateTimePicker::make('reconciliation_date')
                            ->label('Fecha de Conciliación')
                            ->default(now()),
                    ])
                    ->columns(2),

                Section::make('Datos Contables')
                    ->schema([
                        TextInput::make('planned_budget')
                            ->label('Presupuesto Planificado')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $actualSpent = $get('actual_spent');
                                if ($state && $actualSpent) {
                                    $variance = $state - $actualSpent;
                                    $variancePercentage = $state > 0 ? ($variance / $state) * 100 : 0;
                                    $set('variance', $variance);
                                    $set('variance_percentage', $variancePercentage);
                                }
                            }),

                        TextInput::make('actual_spent')
                            ->label('Gasto Real')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $plannedBudget = $get('planned_budget');
                                if ($state && $plannedBudget) {
                                    $variance = $plannedBudget - $state;
                                    $variancePercentage = $plannedBudget > 0 ? ($variance / $plannedBudget) * 100 : 0;
                                    $set('variance', $variance);
                                    $set('variance_percentage', $variancePercentage);
                                }
                            }),

                        TextInput::make('variance')
                            ->label('Variación')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('variance_percentage')
                            ->label('Variación (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Section::make('Notas Adicionales')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activeCampaign.meta_campaign_name')
                    ->label('Campaña')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->weight('bold'),

                TextColumn::make('advertisingPlan.plan_name')
                    ->label('Plan de Publicidad')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('reconciliation_status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                        default => 'Desconocido',
                    }),

                TextColumn::make('planned_budget')
                    ->label('Presupuesto Planificado')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('actual_spent')
                    ->label('Gasto Real')
                    ->money('USD')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('variance')
                    ->label('Variación')
                    ->money('USD')
                    ->sortable()
                    ->color(function ($record) {
                        if ($record->variance > 0) {
                            return 'success'; // Presupuesto mayor al gasto
                        } elseif ($record->variance < 0) {
                            return 'danger'; // Gasto mayor al presupuesto
                        }
                        return 'gray'; // Igual
                    }),

                TextColumn::make('variance_percentage')
                    ->label('Variación (%)')
                    ->suffix('%')
                    ->sortable()
                    ->color(function ($record) {
                        if ($record->variance_percentage > 0) {
                            return 'success';
                        } elseif ($record->variance_percentage < 0) {
                            return 'danger';
                        }
                        return 'gray';
                    }),

                TextColumn::make('reconciliation_date')
                    ->label('Fecha de Conciliación')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reconciliation_status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'completed' => 'Completada',
                    ]),

                Tables\Filters\SelectFilter::make('advertising_plan_id')
                    ->label('Plan de Publicidad')
                    ->relationship('advertisingPlan', 'plan_name'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->reconciliation_status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'reconciliation_status' => 'approved',
                            'reconciliation_date' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Conciliación aprobada')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->reconciliation_status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'reconciliation_status' => 'rejected',
                            'reconciliation_date' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Conciliación rechazada')
                            ->warning()
                            ->send();
                    }),

                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn ($record) => $record->reconciliation_status === 'approved')
                    ->action(function ($record) {
                        $record->update([
                            'reconciliation_status' => 'completed',
                            'reconciliation_date' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Conciliación completada')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()
            ->with(['activeCampaign', 'advertisingPlan']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaignPlanReconciliations::route('/'),
            'create' => Pages\CreateCampaignPlanReconciliation::route('/create'),
            'edit' => Pages\EditCampaignPlanReconciliation::route('/{record}/edit'),
        ];
    }
}