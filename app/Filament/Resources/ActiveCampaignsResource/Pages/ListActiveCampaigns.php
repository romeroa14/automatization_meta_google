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

    protected function getHeaderActions(): array
    {
        return [
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
                        // Limpiar campañas existentes
                        ActiveCampaign::query()->delete();
                        
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
                        
                        // Guardar en base de datos
                        foreach ($campaigns as $campaign) {
                            $campaign->save();
                        }
                        
                        Notification::make()
                            ->title('Campañas cargadas exitosamente')
                            ->body("Se cargaron {$campaigns->count()} campañas activas con su jerarquía completa.")
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
