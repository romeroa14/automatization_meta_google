<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use App\Models\FacebookAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Services\GoogleSlidesReportService;
use App\Services\PdfReportService;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?string $modelLabel = 'Reporte';

    protected static ?string $pluralModelLabel = 'Reportes';

    protected static ?string $navigationLabel = 'Reportes Estadísticos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Básica')
                    ->description('Configuración general del reporte')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Reporte')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Reporte Mensual - Septiembre 2024'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Descripción opcional del reporte'),
                        
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('period_start')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->default(now()->startOfMonth())
                                    ->maxDate(now()),
                                
                                Forms\Components\DatePicker::make('period_end')
                                    ->label('Fecha de Fin')
                                    ->required()
                                    ->default(now())
                                    ->minDate(fn ($get) => $get('period_start'))
                                    ->maxDate(now()),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Configuración de Cuentas')
                    ->description('Selecciona las cuentas de Facebook y campañas a incluir')
                    ->schema([
                        Forms\Components\Select::make('selected_facebook_accounts')
                            ->label('Cuentas de Facebook')
                            ->multiple()
                            ->options(FacebookAccount::active()->pluck('account_name', 'id'))
                            ->searchable()
                            ->placeholder('Selecciona las cuentas de Facebook')
                            ->helperText('Selecciona las cuentas de Facebook que quieres incluir en el reporte')
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // Limpiar campos dependientes cuando cambian las cuentas
                                $set('selected_ads', []);
                                $set('fan_pages_order', []);
                            }),
                        
                        Forms\Components\Select::make('selected_ads')
                            ->label('Anuncios Específicos (Opcional)')
                            ->multiple()
                            ->searchable()
                            ->placeholder('Deja vacío para incluir todos los anuncios configurados')
                            ->helperText('Si seleccionas anuncios específicos, solo se incluirán esos en el reporte')
                            ->options(function ($get) {
                                $accountIds = $get('selected_facebook_accounts');
                                if (empty($accountIds)) {
                                    return [];
                                }
                                
                                $accounts = FacebookAccount::whereIn('id', $accountIds)->get();
                                $ads = [];
                                
                                foreach ($accounts as $account) {
                                    if ($account->selected_ad_ids) {
                                        foreach ($account->selected_ad_ids as $adId) {
                                            $ads[$adId] = "Anuncio {$adId} - {$account->account_name}";
                                        }
                                    }
                                }
                                
                                return $ads;
                            })
                            ->live()
                            ->visible(fn ($get) => !empty($get('selected_facebook_accounts'))),
                    ])
                    ->collapsible(),

                Section::make('Configuración de Orden')
                    ->description('Define el orden de las Fan Pages en el reporte')
                    ->schema([
                        Forms\Components\Select::make('fan_pages_order')
                            ->label('Orden de Fan Pages')
                            ->multiple()
                            ->options(function ($get) {
                                $accountIds = $get('selected_facebook_accounts');
                                if (empty($accountIds)) {
                                    return [];
                                }
                                
                                $accounts = FacebookAccount::whereIn('id', $accountIds)->get();
                                $options = [];
                                
                                foreach ($accounts as $account) {
                                    $options[$account->id] = $account->account_name;
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->placeholder('Selecciona el orden de las Fan Pages')
                            ->helperText('El orden en que aparecerán las Fan Pages en el reporte (primero = primera)')
                            ->live()
                            ->visible(fn ($get) => !empty($get('selected_facebook_accounts'))),
                    ])
                    ->collapsible(),

                Section::make('Configuración de Estadísticas')
                    ->description('Selecciona qué métricas incluir en el reporte')
                    ->schema([
                        Forms\Components\CheckboxList::make('statistics_config')
                            ->label('Métricas a Incluir')
                            ->options([
                                'basic' => 'Métricas Básicas (Impresiones, Clicks, Alcance, Gasto)',
                                'performance' => 'Métricas de Rendimiento (CTR, CPM, CPC, Frecuencia)',
                                'engagement' => 'Métricas de Engagement (Interacciones, Tasa de Interacción)',
                                'video' => 'Métricas de Video (Vistas, Tasa de Finalización)',
                                'geographic' => 'Datos Geográficos (País, Región)',
                                'instagram' => 'Métricas de Instagram (Seguidores, Alcance)',
                            ])
                            ->default(['basic', 'performance', 'engagement'])
                            ->columns(2)
                            ->helperText('Selecciona las categorías de métricas que quieres incluir en el reporte'),
                    ])
                    ->collapsible(),

                Section::make('Configuración de Gráficas')
                    ->description('Configura las gráficas que se generarán al final del reporte')
                    ->schema([
                        Repeater::make('charts_config')
                            ->label('Gráficas')
                            ->schema([
                                Forms\Components\Select::make('chart_type')
                                    ->label('Tipo de Gráfica')
                                    ->options([
                                        'bar' => 'Gráfica de Barras',
                                        'line' => 'Gráfica de Líneas',
                                        'pie' => 'Gráfica Circular',
                                        'doughnut' => 'Gráfica de Donut',
                                    ])
                                    ->required(),
                                
                                Forms\Components\TextInput::make('chart_title')
                                    ->label('Título de la Gráfica')
                                    ->required()
                                    ->placeholder('Ej: Alcance Total por Marca'),
                                
                                Forms\Components\Select::make('metric')
                                    ->label('Métrica a Graficar')
                                    ->options([
                                        'reach' => 'Alcance',
                                        'impressions' => 'Impresiones',
                                        'clicks' => 'Clicks',
                                        'spend' => 'Gasto',
                                        'ctr' => 'CTR',
                                        'cpm' => 'CPM',
                                        'cpc' => 'CPC',
                                        'total_interactions' => 'Total de Interacciones',
                                        'interaction_rate' => 'Tasa de Interacción',
                                        'video_views_p100' => 'Vistas de Video al 100%',
                                        'video_completion_rate' => 'Tasa de Finalización de Video',
                                    ])
                                    ->required(),
                                
                                Forms\Components\Select::make('group_by')
                                    ->label('Agrupar Por')
                                    ->options([
                                        'brand' => 'Marca',
                                        'campaign' => 'Campaña',
                                        'date' => 'Fecha',
                                    ])
                                    ->required(),
                                
                                Forms\Components\Toggle::make('include_totals')
                                    ->label('Incluir Totales')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Gráfica')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['chart_title'] ?? 'Nueva Gráfica'
                            ),
                    ])
                    ->collapsible(),

                Section::make('Configuraciones Adicionales')
                    ->description('Otras configuraciones del reporte')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Reporte Activo')
                            ->default(true)
                            ->helperText('Los reportes inactivos no se generarán automáticamente'),
                        
                        KeyValue::make('settings')
                            ->label('Configuraciones Adicionales')
                            ->keyLabel('Configuración')
                            ->valueLabel('Valor')
                            ->helperText('Configuraciones adicionales en formato clave-valor'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                
                TextColumn::make('period_start')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                
                TextColumn::make('period_end')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable(),
                
                TextColumn::make('period_days')
                    ->label('Días')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "{$state} días"),
                
                BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'gray' => 'draft',
                        'yellow' => 'generating',
                        'green' => 'completed',
                        'red' => 'failed',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Borrador',
                        'generating' => 'Generando',
                        'completed' => 'Completado',
                        'failed' => 'Fallido',
                        default => $state,
                    }),
                
                TextColumn::make('total_brands')
                    ->label('Marcas')
                    ->sortable(),
                
                TextColumn::make('total_ads')
                    ->label('Anuncios')
                    ->sortable(),
                
                TextColumn::make('generated_at')
                    ->label('Generado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('No generado'),
                
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'generating' => 'Generando',
                        'completed' => 'Completado',
                        'failed' => 'Fallido',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
                
                Tables\Filters\Filter::make('period')
                    ->label('Período')
                    ->form([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['period_start'],
                                fn (Builder $query, $date): Builder => $query->where('period_start', '>=', $date),
                            )
                            ->when(
                                $data['period_end'],
                                fn (Builder $query, $date): Builder => $query->where('period_end', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Action::make('generate')
                    ->label('Generar Slides')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Report $record) => $record->status === 'draft' || $record->status === 'failed')
                    ->action(function (Report $record) {
                        try {
                            // Actualizar el estado a "generating"
                            $record->update(['status' => 'generating']);
                            
                            // Instanciar el servicio directamente
                            $googleSlidesService = new GoogleSlidesReportService();
                            
                            // Generar el reporte usando el servicio
                            $result = $googleSlidesService->generateReport($record);
                            
                            // Verificar si fue exitoso
                            if ($result['success']) {
                                // Actualizar el reporte con la URL de la presentación
                                $record->update([
                                    'status' => 'completed',
                                    'google_slides_url' => $result['presentation_url'],
                                    'generated_at' => now(),
                                ]);
                                
                                // Mostrar notificación de éxito
                                Notification::make()
                                    ->title('Reporte Generado Exitosamente')
                                    ->body("Se generaron {$result['slides_count']} diapositivas.")
                                    ->success()
                                    ->send();
                                
                                // Abrir en nueva pestaña usando JavaScript
                                return redirect()->away($result['presentation_url']);
                                
                            } else {
                                // Actualizar el estado a "failed"
                                $record->update(['status' => 'failed']);
                                
                                // Mostrar notificación de error
                                Notification::make()
                                    ->title('Error al Generar Slides')
                                    ->body('Error: ' . ($result['error'] ?? 'Error desconocido'))
                                    ->danger()
                                    ->send();
                            }
                            
                        } catch (\Exception $e) {
                            // Actualizar el estado a "failed"
                            $record->update(['status' => 'failed']);
                            
                            // Mostrar notificación de error
                            Notification::make()
                                ->title('Error al Generar Slides')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Action::make('generate_pdf')
                    ->label(fn (Report $record): string => $record->pdf_generated ? 'Ver PDF' : 'Generar PDF')
                    ->icon(fn (Report $record): string => $record->pdf_generated ? 'heroicon-o-eye' : 'heroicon-o-document-arrow-down')
                    ->color(fn (Report $record): string => $record->pdf_generated ? 'success' : 'warning')
                    ->url(fn (Report $record): string => 
                        $record->pdf_generated 
                            ? $record->pdf_url 
                            : route('reports.generate-pdf', $record)
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (Report $record): bool => true),
                
                Action::make('view_slides')
                    ->label('Ver Presentación')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Report $record) => $record->google_slides_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Report $record) => $record->status === 'completed' && $record->google_slides_url),
                
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'view' => Pages\ViewReport::route('/{record}'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 10 ? 'warning' : 'primary';
    }
}
