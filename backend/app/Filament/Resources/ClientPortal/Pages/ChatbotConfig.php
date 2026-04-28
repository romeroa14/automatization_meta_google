<?php

namespace App\Filament\Resources\ClientPortal\Pages;

use App\Models\Tenant;
use App\Models\ChatbotConfig;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Str;

class ChatbotConfig extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Configuración Chatbot';
    protected static string $view = 'filament.resources.client-portal.pages.chatbot-config';

    public $tenant;
    public $config;
    public $systemPrompt = '';
    public $behaviors = [];
    public $skills = [];

    public function mount(): void
    {
        $this->tenant = Tenant::findBySlug('ads_vnzla');
        
        // Load or create config
        $this->config = ChatbotConfig::where('tenant_id', $this->tenant->id)->first();
        
        if (!$this->config) {
            $this->config = ChatbotConfig::create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Chatbot ' . $this->tenant->name,
                'system_prompt' => "Eres un asistente amigable para {$this->tenant->name}. Responde en español de manera breve y útil.",
                'fallback_message' => 'Gracias por tu mensaje. Un asesor se comunicará contigo pronto.',
            ]);
        }

        $this->systemPrompt = $this->config->system_prompt;
        $this->behaviors = $this->config->behaviors ?? [];
        $this->skills = $this->config->skills ?? [];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('System Prompt')
                ->description('Instrucciones principales del chatbot')
                ->schema([
                    Forms\Components\Textarea::make('system_prompt')
                        ->label('Prompt del Sistema')
                        ->rows(8)
                        ->placeholder('Eres un asistente que...')
                        ->helperText('Este promptdefine cómo behave el chatbot'),
                ]),
            Forms\Components\Section::make('Comportamientos')
                ->description('Comportamientos activos del chatbot')
                ->schema([
                    Forms\Components\CheckboxList::make('behaviors')
                        ->label('Activar comportamientos')
                        ->options([
                            'friendly' => 'Amigable',
                            'formal' => 'Formal',
                            'sales_oriented' => 'Orientado a ventas',
                            'support_oriented' => 'Orientado a soporte',
                            'informative' => 'Informativo',
                        ])
                        ->columns(2),
                ]),
            Forms\Components\Section::make('Skills')
                ->description('Habilidades del chatbot')
                ->schema([
                    Forms\Components\CheckboxList::make('skills')
                        ->label('Activar skills')
                        ->options([
                            'search_products' => 'Buscar productos',
                            'get_pricing' => 'Dar precios',
                            'create_lead' => 'Crear lead',
                            'schedule_meeting' => 'Agendar reunión',
                            'answer_faq' => 'Responder FAQ',
                        ])
                        ->columns(2),
                ]),
            Forms\Components\Section::make('Mensaje de Fallback')
                ->schema([
                    Forms\Components\Textarea::make('fallback_message')
                        ->label('Mensaje cuando no sep responder')
                        ->rows(3),
                ]),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $this->config->update([
            'system_prompt' => $data['system_prompt'],
            'behaviors' => $data['behaviors'] ?? [],
            'skills' => $data['skills'] ?? [],
            'fallback_message' => $data['fallback_message'],
        ]);
        
        session()->flash('success', 'Configuración guardada correctamente');
    }

    protected function getActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar Configuración')
                ->submit('save')
                ->color('primary'),
        ];
    }
}