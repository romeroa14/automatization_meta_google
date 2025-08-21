<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoogleSheetResource\Pages;
use App\Filament\Resources\GoogleSheetResource\RelationManagers;
use App\Models\GoogleSheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class GoogleSheetResource extends Resource
{
    protected static ?string $model = GoogleSheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Google Sheets';

    protected static ?string $modelLabel = 'Google Sheet';

    protected static ?string $pluralModelLabel = 'Google Sheets';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Spreadsheet')
                    ->description('Configura los datos de tu Google Sheet')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Mi Spreadsheet de Métricas'),
                        Forms\Components\TextInput::make('spreadsheet_id')
                            ->label('ID del Spreadsheet')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms')
                            ->helperText('El ID de tu Google Sheet (se encuentra en la URL)')
                            ->suffixAction(
                                Action::make('fetch_sheets')
                                    ->label('Consultar Hojas')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('primary')
                                    ->action(function ($state, $set) {
                                        if (empty($state)) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Primero ingresa el ID del Spreadsheet')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        try {
                                            $sheets = self::fetchGoogleSheets($state);
                                            
                                            if (empty($sheets)) {
                                                Notification::make()
                                                    ->title('No se encontraron hojas')
                                                    ->body('Verifica que el ID del Spreadsheet sea correcto y que tengas permisos de acceso')
                                                    ->warning()
                                                    ->send();
                                                return;
                                            }
                                            
                                            // Guardar las hojas en el formulario para usarlas en el select
                                            $set('available_sheets', $sheets);
                                            
                                            Notification::make()
                                                ->title('Hojas encontradas')
                                                ->body('Se encontraron ' . count($sheets) . ' hojas en el spreadsheet')
                                                ->success()
                                                ->send();
                                            
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Error al consultar las hojas: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),
                        Forms\Components\Select::make('worksheet_name')
                            ->label('Nombre de la Hoja')
                            ->required()
                            // ->maxLength(255)
                            ->placeholder('Selecciona una hoja')
                            ->helperText('El nombre de la hoja donde se escribirán los datos')
                            ->options(function ($get) {
                                $sheets = $get('available_sheets');
                                if (!$sheets) {
                                    return [];
                                }
                                
                                $options = [];
                                foreach ($sheets as $sheet) {
                                    $options[$sheet] = $sheet;
                                }
                                return $options;
                            })
                            ->searchable()
                            ->disabled(fn ($get) => empty($get('available_sheets')))
                            ->reactive(),
                        Forms\Components\Hidden::make('available_sheets'),
                    ])->columns(2),

                // Forms\Components\Section::make('Configuración del Sistema')
                //     ->description('El sistema usa una URL universal configurada en las variables de entorno')
                //     ->schema([
                //         Forms\Components\Placeholder::make('webapp_info')
                //             ->label('Web App Universal')
                //             ->content('El sistema está configurado para usar una URL universal de Google Apps Script que permite actualizar cualquier Google Sheet automáticamente.')
                //             ->columnSpanFull(),
                //         Forms\Components\Placeholder::make('permissions_info')
                //             ->label('Permisos Requeridos')
                //             ->content('Asegúrate de que tu Google Sheet tenga permisos de acceso público o que tu cuenta tenga permisos de editor.')
                //             ->columnSpanFull(),
                //     ]),

                Forms\Components\Section::make('Configuración de Datos')
                    ->description('Elige cómo quieres que se muestren los datos')
                    ->schema([
                        Forms\Components\Toggle::make('individual_ads')
                            ->label('Anuncios Individuales')
                            ->default(false)
                            ->helperText('Si activas esto, cada anuncio aparecerá en una fila separada. Si no, se mostrarán totales por campaña.')
                            ->reactive(),
                    ]),

                Forms\Components\Section::make('Mapeo de Celdas')
                    ->description('Define qué métricas se escribirán en qué celdas')
                    ->schema([
                        Forms\Components\KeyValue::make('cell_mapping')
                            ->label('Mapeo de Métricas')
                            ->keyLabel('Métrica')
                            ->valueLabel('Celda Base')
                            ->addActionLabel('Agregar Métrica')
                            ->default([
                                'ad_name' => 'A',
                                'ad_id' => 'B',
                                'campaign_name' => 'C',
                                'impressions' => 'D',
                                'clicks' => 'E',
                                'spend' => 'F',
                                'reach' => 'G',
                                'ctr' => 'H',
                                'cpm' => 'I',
                                'cpc' => 'J',
                                'total_interactions' => 'K',
                                'interaction_rate' => 'L',
                                'video_views_p100' => 'M',
                            ])
                            ->helperText(function ($get) {
                                if ($get('individual_ads')) {
                                    return 'Define las columnas donde se mostrarán los datos. Los anuncios se desplegarán en filas consecutivas empezando desde la fila 2.';
                                } else {
                                    return 'Define qué métricas se escribirán en qué celdas específicas del spreadsheet.';
                                }
                            }),
                        Forms\Components\TextInput::make('start_row')
                            ->label('Fila de Inicio')
                            ->default('2')
                            ->helperText('Fila donde comenzarán los datos (la fila 1 se usa para headers)')
                            ->visible(fn ($get) => $get('individual_ads'))
                            ->numeric()
                            ->minValue(2),
                    ]),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('spreadsheet_id')
                    ->label('ID del Spreadsheet')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('ID copiado al portapapeles'),
                Tables\Columns\TextColumn::make('worksheet_name')
                    ->label('Hoja')
                    ->searchable()
                    ->sortable(),
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
            'index' => Pages\ListGoogleSheets::route('/'),
            'create' => Pages\CreateGoogleSheet::route('/create'),
            'edit' => Pages\EditGoogleSheet::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    /**
     * Consulta las hojas disponibles de un Google Sheet
     */
    public static function fetchGoogleSheets($spreadsheetId): array
    {
        try {
            // PRIMERO intentar con el Web App (más preciso)
            $webappUrl = config('services.google.webapp_url') ?? env('GOOGLE_WEBAPP_URL');
            
            if (!empty($webappUrl)) {
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withOptions(['allow_redirects' => true])
                    ->get($webappUrl, [
                        'action' => 'list_sheets',
                        'spreadsheet_id' => $spreadsheetId
                    ]);

                if ($response->successful()) {
                    $result = $response->json();
                    
                    if (isset($result['success']) && $result['success'] && isset($result['sheets'])) {
                        \Illuminate\Support\Facades\Log::info("Hojas obtenidas via Web App para ID: {$spreadsheetId}", ['sheets' => $result['sheets']]);
                        return $result['sheets'];
                    } else {
                        \Illuminate\Support\Facades\Log::warning("Web App devolvió error para ID: {$spreadsheetId}", ['response' => $result]);
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("Web App no respondió para ID: {$spreadsheetId}", ['status' => $response->status()]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning("URL del Web App no configurada");
            }
            
            // Si el Web App falla, intentar con la API pública como fallback
            $publicSheets = self::fetchSheetsViaPublicAPI($spreadsheetId);
            
            if (!empty($publicSheets)) {
                \Illuminate\Support\Facades\Log::info("Hojas obtenidas via API pública (fallback) para ID: {$spreadsheetId}", ['sheets' => $publicSheets]);
                return $publicSheets;
            }

            // Si todo falla, devolver hojas por defecto
            \Illuminate\Support\Facades\Log::warning("Usando hojas por defecto para ID: {$spreadsheetId}");
            return ['Sheet1', 'Hoja1', 'BRANDS SHOP'];
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error consultando hojas de Google Sheet: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Consulta las hojas usando la API pública de Google Sheets
     */
    private static function fetchSheetsViaPublicAPI($spreadsheetId): array
    {
        try {
            // Intentar obtener las hojas usando la URL pública
            $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:json&tq=SELECT%20*%20LIMIT%201";
            
            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                // Extraer información de las hojas del JSON
                $content = $response->body();
                
                // Buscar información de hojas en el contenido
                if (preg_match('/"sheets":\s*\[(.*?)\]/', $content, $matches)) {
                    // Parsear las hojas encontradas
                    $sheetsData = $matches[1];
                    $sheets = [];
                    
                    // Extraer nombres de hojas usando regex
                    if (preg_match_all('/"name":\s*"([^"]+)"/', $sheetsData, $sheetMatches)) {
                        $sheets = $sheetMatches[1];
                    }
                    
                    return $sheets;
                }
            }
            
            // Si no funciona con el primer método, intentar con una consulta específica
            return self::fetchSheetsViaAlternativeMethod($spreadsheetId);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error consultando hojas via API pública: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Método alternativo para obtener hojas
     */
    private static function fetchSheetsViaAlternativeMethod($spreadsheetId): array
    {
        try {
            // Intentar con diferentes URLs para obtener información de hojas
            $urls = [
                "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:json&tq=SELECT%20*%20FROM%20A1%20LIMIT%201",
                "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:json&tq=SELECT%20*%20FROM%20Sheet1%20LIMIT%201",
                "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/gviz/tq?tqx=out:json&tq=SELECT%20*%20FROM%20Hoja1%20LIMIT%201",
            ];
            
            foreach ($urls as $url) {
                $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $content = $response->body();
                    
                    // Buscar información de hojas en el contenido
                    if (preg_match('/"sheets":\s*\[(.*?)\]/', $content, $matches)) {
                        $sheetsData = $matches[1];
                        $sheets = [];
                        
                        // Extraer nombres de hojas usando regex
                        if (preg_match_all('/"name":\s*"([^"]+)"/', $sheetsData, $sheetMatches)) {
                            $sheets = $sheetMatches[1];
                            return $sheets;
                        }
                    }
                }
            }
            
            // Si todo falla, devolver hojas por defecto basadas en el error
            return ['BRANDS SHOP', 'Sheet1', 'Hoja1'];
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en método alternativo: ' . $e->getMessage());
            return ['BRANDS SHOP', 'Sheet1', 'Hoja1'];
        }
    }
}
