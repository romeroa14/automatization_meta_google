<?php

namespace App\Filament\Resources\ClientPortal\Pages;

use App\Models\Tenant;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\UsesTables;
use Filament\Tables\Table;
use Filament\Pages\Page;

class Leads extends Page implements HasTable
{
    use Tables\Concerns\UsesTables;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Leads';
    protected static string $view = 'filament.resources.client-portal.pages.leads';

    public $tenant;

    public function mount(): void
    {
        $this->tenant = Tenant::findBySlug('ads_vnzla');
    }

    public function getTable(Table $table): Table
    {
        return $table
            ->query(
                Lead::where('tenant_id', $this->tenant->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('intent')
                    ->label('Intención')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'compra' => 'success',
                        'consulta' => 'info',
                        'reclamo' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('stage')
                    ->label('Etapa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nuevo' => 'gray',
                        'contactado' => 'warning',
                        'interesado' => 'info',
                        'cliente' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confianza')
                    ->numeric(2)
                    ->color(fn (float $state): string => match (true) {
                        $state >= 0.8 => 'success',
                        $state >= 0.5 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->options([
                        'nuevo' => 'Nuevo',
                        'contactado' => 'Contactado',
                        'interesado' => 'Interesado',
                        'cliente' => 'Cliente',
                    ]),
                Tables\Filters\SelectFilter::make('intent')
                    ->options([
                        'compra' => 'Compra',
                        'consulta' => 'Consulta',
                        'reclamo' => 'Reclamo',
                    ]),
            ])
            ->paginated(20);
    }
}