<?php

namespace App\Filament\Resources\ActiveCampaignsResource\Pages;

use App\Filament\Resources\ActiveCampaignsResource;
use App\Models\ActiveCampaign;
use App\Models\FacebookAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Form;

class ListActiveCampaigns extends ListRecords
{
    protected static string $resource = ActiveCampaignsResource::class;
    protected static ?string $navigationLabel = 'Campañas Activas';
    protected static ?string $navigationGroup = 'ADMETRICAS.COM';
    protected static ?int $navigationSort = 2;

    protected function getHeaderActions(): array
    {
        return [
            
                
            Action::make('auto_reconcile_campaigns')
                ->label('Conciliar Automáticamente')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->action(function () {
                    $service = new \App\Services\CampaignReconciliationService();
                    $results = $service->processActiveCampaigns();
                    
                    Notification::make()
                        ->title('Conciliación Automática Completada')
                        ->body("Procesadas: {$results['processed']} campañas | Conciliadas: {$results['reconciled']} | Errores: " . count($results['errors']))
                        ->success()
                        ->send();
                }),
                
            Action::make('refresh_spend_data')
                ->label('Actualizar Gastos')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    $updated = 0;
                    foreach (ActiveCampaign::all() as $record) {
                        try {
                            $campaignId = $record->meta_campaign_id;
                            if (!$campaignId) {
                                continue;
                            }
                            
                            // Buscar token de la cuenta asociada
                            $facebookAccount = null;
                            if (isset($record->facebook_account_id)) {
                                $facebookAccount = FacebookAccount::find($record->facebook_account_id);
                            }
                            if (!$facebookAccount || !$facebookAccount->access_token) {
                                continue;
                            }
                            
                            $token = $facebookAccount->access_token;
                            // Obtener gastos de los últimos 30 días (rango amplio para datos recientes)
                            $url = "https://graph.facebook.com/v18.0/{$campaignId}/insights?fields=spend&time_range[since]=" . urlencode(now()->subDays(30)->format('Y-m-d')) . "&time_range[until]=" . urlencode(now()->format('Y-m-d')) . "&access_token={$token}";
                            $response = ActiveCampaign::makeHttpRequest($url);
                            if ($response === false) {
                                continue;
                            }
                            
                            $json = json_decode($response, true);
                            $spend = 0;
                            if (isset($json['data']) && is_array($json['data'])) {
                                foreach ($json['data'] as $row) {
                                    $spend += (float)($row['spend'] ?? 0);
                                }
                            }
                            
                            // Guardar gastos actualizados
                            $campaignData = $record->campaign_data ?? [];
                            $campaignData['amount_spent_override'] = $spend;
                            $campaignData['last_updated'] = now()->toISOString();
                            $record->campaign_data = $campaignData;
                            $record->save();
                            $updated++;
                        } catch (\Throwable $e) {
                            continue;
                        }
                    }

                    Notification::make()
                        ->title('Gastos actualizados')
                        ->body("Se actualizaron los gastos de {$updated} campañas con datos recientes de Meta API")
                        ->success()
                        ->send();
                }),
                
            Action::make('date_range_stats')
                ->label('Actualizar Gastos por Rango')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->modalHeading('Actualizar gastos reales de las campañas por rango de fechas')
                ->modalDescription('Selecciona el rango de fechas para obtener los gastos reales desde Meta API y actualizar la tabla.')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Fecha de Inicio*')
                        ->required()
                        ->default(now()->subDays(7)->format('Y-m-d'))
                        ->displayFormat('d/m/Y')
                        ->native(false),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('Fecha de Fin*')
                        ->required()
                        ->default(now()->format('Y-m-d'))
                        ->displayFormat('d/m/Y')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $startDate = $data['start_date'];
                    $endDate = $data['end_date'];

                    $updated = 0;
                    foreach (ActiveCampaign::all() as $record) {
                        try {
                            $campaignId = $record->meta_campaign_id;
                            if (!$campaignId) {
                                continue;
                            }
                            // Buscar token de la cuenta asociada si existe
                            $facebookAccount = null;
                            if (isset($record->facebook_account_id)) {
                                $facebookAccount = FacebookAccount::find($record->facebook_account_id);
                            }
                            if (!$facebookAccount || !$facebookAccount->access_token) {
                                continue;
                            }
                            $token = $facebookAccount->access_token;
                            $url = "https://graph.facebook.com/v18.0/{$campaignId}/insights?fields=spend&time_range[since]=" . urlencode($startDate) . "&time_range[until]=" . urlencode($endDate) . "&access_token={$token}";
                            $response = ActiveCampaign::makeHttpRequest($url);
                            if ($response === false) {
                                continue;
                            }
                            $json = json_decode($response, true);
                            $spend = 0;
                            if (isset($json['data']) && is_array($json['data'])) {
                                foreach ($json['data'] as $row) {
                                    $spend += (float)($row['spend'] ?? 0);
                                }
                            }
                            // Guardar override en el JSON de campaña
                            $campaignData = $record->campaign_data ?? [];
                            $campaignData['amount_spent_override'] = $spend;
                            $campaignData['amount_spent_range'] = [
                                'since' => $startDate,
                                'until' => $endDate,
                            ];
                            $record->campaign_data = $campaignData;
                            $record->save();
                            $updated++;
                        } catch (\Throwable $e) {
                            // Continuar con el siguiente registro
                            continue;
                        }
                    }

                    Notification::make()
                        ->title('Rango de fechas aplicado exitosamente')
                        ->body("Se actualizaron {$updated} campañas con el gasto del rango {$startDate} - {$endDate}")
                        ->success()
                        ->send();
                }),
            Action::make('load_campaigns')
                ->label('Cargar Campañas Activas')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('success')
                ->modalHeading('Cargar Campañas Activas desde Meta Ads')
                ->modalDescription('Selecciona la cuenta de Facebook y la cuenta publicitaria para cargar todas las campañas activas con su jerarquía completa.')
                ->form([
                    Section::make('Selección de Cuenta')
                        ->schema([
                            Select::make('facebook_account_id')
                                ->label('Cuenta de Facebook')
                                ->options(FacebookAccount::pluck('account_name', 'id'))
                                ->required()
                                ->searchable()
                                ->placeholder('Selecciona una cuenta de Facebook')
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    $set('selected_ad_account_id', null);
                                }),
                                
                            Select::make('selected_ad_account_id')
                                ->label('Cuenta Publicitaria')
                                ->options(function ($get) {
                                    // Usar las opciones cargadas dinámicamente
                                    $accountOptions = $get('account_options') ?? [];
                                    return $accountOptions;
                                })
                                ->required()
                                ->searchable()
                                ->placeholder('Selecciona una cuenta publicitaria')
                                ->disabled(fn ($get) => !$get('facebook_account_id'))
                                ->suffixAction(
                                    \Filament\Forms\Components\Actions\Action::make('refresh_ad_accounts')
                                        ->label('Refrescar')
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('info')
                                        ->action(function ($state, $set, $get) {
                                            $facebookAccountId = $get('facebook_account_id');
                                            
                                            if (!$facebookAccountId) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Error')
                                                    ->body('Selecciona una cuenta de Facebook primero.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                            
                                            $facebookAccount = FacebookAccount::find($facebookAccountId);
                                            if (!$facebookAccount || !$facebookAccount->access_token) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Error')
                                                    ->body('La cuenta seleccionada no tiene token de acceso configurado.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }
                                            
                                            try {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Cargando cuentas...')
                                                    ->body('Obteniendo cuentas publicitarias de Facebook. Esto puede tomar unos segundos.')
                                                    ->info()
                                                    ->send();
                                                
                                                $url = "https://graph.facebook.com/v18.0/me/adaccounts?limit=250&access_token={$facebookAccount->access_token}";
                                                $response = file_get_contents($url);
                                                $data = json_decode($response, true);
                                                
                                                if (!isset($data['data'])) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Error')
                                                        ->body('No se pudieron obtener las cuentas publicitarias')
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }
                                                
                                                $accountOptions = [];
                                                foreach ($data['data'] as $account) {
                                                    $accountId = str_replace('act_', '', $account['id']);
                                                    $accountName = $account['name'] ?? 'Cuenta ' . $accountId;
                                                    $accountOptions[$accountId] = $accountName . ' (ID: ' . $accountId . ')';
                                                }
                                                
                                                $set('account_options', $accountOptions);
                                                $set('selected_ad_account_id', null);
                                                
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Cuentas Actualizadas')
                                                    ->body("Se encontraron " . count($accountOptions) . " cuentas publicitarias en tu cuenta de Facebook")
                                                    ->success()
                                                    ->send();
                                                    
                                            } catch (\Exception $e) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Error')
                                                    ->body('Error obteniendo cuentas: ' . $e->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        })
                                ),
                                
                            // Campo oculto para almacenar las opciones
                            \Filament\Forms\Components\Hidden::make('account_options'),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    try {
                        // Limpiar campañas existentes SOLO de esta cuenta publicitaria
                        ActiveCampaign::where('ad_account_id', $data['selected_ad_account_id'])->delete();
                        
                        // Cargar nuevas campañas
                        $campaigns = ActiveCampaign::getActiveCampaignsHierarchy(
                            $data['facebook_account_id'],
                            $data['selected_ad_account_id']
                        );
                        
                        if ($campaigns->isEmpty()) {
                            Notification::make()
                                ->title('No se encontraron campañas')
                                ->body('No se encontraron campañas activas en la cuenta publicitaria seleccionada.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Guardar en base de datos con detección de múltiples planes
                        $multiplePlansDetected = 0;
                        $totalCampaigns = 0;
                        
                        foreach ($campaigns as $campaign) {
                            // LÓGICA CONDICIONAL: Evaluar si no tiene stop_time
                            $hasStopTime = $campaign->campaign_stop_time || $campaign->adset_stop_time;
                            $dailyBudget = $campaign->campaign_daily_budget ?? $campaign->adset_daily_budget ?? 0;
                            
                            // Si no tiene stop_time, calcular presupuesto total desde el nombre
                            $totalBudget = 0;
                            if (!$hasStopTime && $dailyBudget > 0) {
                                // Calcular duración desde el nombre de la campaña
                                $duration = self::calculateDurationFromName($campaign->meta_campaign_name);
                                if ($duration > 0) {
                                    $totalBudget = $dailyBudget * $duration;
                                }
                            }
                            
                            // Preparar datos para detección de múltiples planes
                            $campaignData = [
                                'id' => $campaign->meta_campaign_id,
                                'name' => $campaign->meta_campaign_name,
                                'status' => $campaign->campaign_status,
                                'start_time' => $campaign->campaign_start_time?->toISOString(),
                                'stop_time' => $campaign->campaign_stop_time?->toISOString(),
                                'daily_budget' => $dailyBudget, // Presupuesto diario de la API
                                'total_budget' => $totalBudget, // Presupuesto total calculado desde nombre
                                'amount_spent' => $campaign->amount_spent
                            ];
                            
                            // Usar detección de múltiples planes
                            $result = ActiveCampaign::detectAndCreateMultiplePlans($campaignData, $data['facebook_account_id'], $data['selected_ad_account_id']);
                            
                            if ($result) {
                                $totalCampaigns++;
                                
                                // Verificar si se detectaron múltiples planes
                                if ($result->campaign_data['multiple_plan'] ?? false) {
                                    $multiplePlansDetected++;
                                }
                            }
                        }
                        
                        $message = "Se cargaron {$totalCampaigns} campañas activas";
                        if ($multiplePlansDetected > 0) {
                            $message .= " con {$multiplePlansDetected} campañas que tienen múltiples planes detectados";
                        }
                        
                        Notification::make()
                            ->title('Campañas cargadas exitosamente')
                            ->body($message)
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al cargar campañas')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalSubmitActionLabel('Cargar Campañas')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }
}
