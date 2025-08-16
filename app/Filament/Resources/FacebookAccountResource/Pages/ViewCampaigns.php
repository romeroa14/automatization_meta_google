<?php

namespace App\Filament\Resources\FacebookAccountResource\Pages;

use App\Filament\Resources\FacebookAccountResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Support\Facades\Log;
use App\Models\FacebookAccount; // Added this import for FacebookAccount model

class ViewCampaigns extends Page
{
    use InteractsWithForms;

    protected static string $resource = FacebookAccountResource::class;

    protected static string $view = 'filament.resources.facebook-account-resource.pages.view-campaigns';

    public $record;
    public $selectedCampaign = null;
    public $campaigns = [];
    public $ads = [];
    public $dateRange = 'last_7d';
    public $isLoading = false;

    public function mount($record): void
    {
        // Si record es un string (ID), cargar el modelo
        if (is_string($record)) {
            $this->record = FacebookAccount::findOrFail($record);
        } else {
            $this->record = $record;
        }
        
        $this->loadCampaigns();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Selección de Campaña')
                    ->description('Selecciona una campaña para ver sus anuncios y métricas')
                    ->schema([
                        Select::make('selectedCampaign')
                            ->label('Campaña')
                            ->options($this->getCampaignOptions())
                            ->placeholder('Selecciona una campaña')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    $this->loadAds($state);
                                }
                            }),
                            
                        Select::make('dateRange')
                            ->label('Período de Datos')
                            ->options([
                                'last_7d' => 'Últimos 7 días',
                                'last_14d' => 'Últimos 14 días',
                                'last_30d' => 'Últimos 30 días',
                                'last_90d' => 'Últimos 90 días',
                                'this_month' => 'Este mes',
                                'last_month' => 'Mes pasado',
                            ])
                            ->default('last_7d')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($this->selectedCampaign) {
                                    $this->loadAds($this->selectedCampaign);
                                }
                            }),
                    ])->columns(2),
            ]);
    }

    public function loadCampaigns(): void
    {
        try {
            $this->isLoading = true;
            
            // Inicializar Facebook API
            Api::init(
                $this->record->app_id,
                $this->record->app_secret,
                $this->record->access_token
            );
            
            $account = new AdAccount('act_' . $this->record->account_id);
            
            // Obtener campañas
            $campaigns = $account->getCampaigns(['id', 'name', 'status']);
            $this->campaigns = collect($campaigns)->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                ];
            })->toArray();
            
            $this->isLoading = false;
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            Log::error('Error cargando campañas: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('Error al cargar las campañas: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function loadAds(string $campaignId): void
    {
        try {
            $this->isLoading = true;
            
            // Inicializar Facebook API
            Api::init(
                $this->record->app_id,
                $this->record->app_secret,
                $this->record->access_token
            );
            
            $account = new AdAccount('act_' . $this->record->account_id);
            
            // Obtener anuncios de la campaña con métricas
            $fields = [
                'ad_id',
                'ad_name',
                'adset_id',
                'campaign_id',
                'impressions',
                'clicks',
                'spend',
                'reach',
                'frequency',
                'ctr',
                'cpm',
                'cpc',
            ];

            $params = [
                'level' => 'ad',
                'time_range' => ['since' => $this->getDateRangeStart($this->dateRange), 'until' => now()->format('Y-m-d')],
                'filtering' => [
                    [
                        'field' => 'campaign.id',
                        'operator' => 'IN',
                        'value' => [$campaignId],
                    ],
                ],
            ];

            $insights = $account->getInsights($fields, $params);
            
            $this->ads = collect($insights)->map(function ($insight) {
                return [
                    'ad_id' => $insight->ad_id ?? null,
                    'ad_name' => $insight->ad_name ?? 'Sin nombre',
                    'impressions' => (int)($insight->impressions ?? 0),
                    'clicks' => (int)($insight->clicks ?? 0),
                    'spend' => (float)($insight->spend ?? 0),
                    'reach' => (int)($insight->reach ?? 0),
                    'frequency' => (float)($insight->frequency ?? 0),
                    'ctr' => (float)($insight->ctr ?? 0),
                    'cpm' => (float)($insight->cpm ?? 0),
                    'cpc' => (float)($insight->cpc ?? 0),
                ];
            })->toArray();
            
            $this->isLoading = false;
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            Log::error('Error cargando anuncios: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error')
                ->body('Error al cargar los anuncios: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getCampaignOptions(): array
    {
        return collect($this->campaigns)->pluck('name', 'id')->toArray();
    }

    private function getDateRangeStart(string $dateRange): string
    {
        return match($dateRange) {
            'last_7d' => now()->subDays(7)->format('Y-m-d'),
            'last_14d' => now()->subDays(14)->format('Y-m-d'),
            'last_30d' => now()->subDays(30)->format('Y-m-d'),
            'last_90d' => now()->subDays(90)->format('Y-m-d'),
            'this_month' => now()->startOfMonth()->format('Y-m-d'),
            'last_month' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            default => now()->subDays(7)->format('Y-m-d'),
        };
    }

    public function getTitle(): string
    {
        // Asegurar que tenemos el objeto FacebookAccount
        if (is_string($this->record)) {
            $this->record = FacebookAccount::findOrFail($this->record);
        }
        
        return "Campañas de {$this->record->account_name}";
    }
} 