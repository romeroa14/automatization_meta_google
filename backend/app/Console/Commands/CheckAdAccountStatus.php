<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\FacebookAccount;

class CheckAdAccountStatus extends Command
{
    protected $signature = 'meta:check-ad-account {facebook_account_id} {ad_account_id}';
    protected $description = 'Verifica el estado y restricciones de una cuenta publicitaria';

    public function handle()
    {
        $facebookAccountId = $this->argument('facebook_account_id');
        $adAccountId = $this->argument('ad_account_id');
        
        $this->info("🔍 **VERIFICACIÓN DE CUENTA PUBLICITARIA**");
        $this->info("Facebook Account ID: {$facebookAccountId}");
        $this->info("Ad Account ID: {$adAccountId}");
        $this->newLine();

        // Obtener cuenta de Facebook
        $facebookAccount = FacebookAccount::find($facebookAccountId);
        
        if (!$facebookAccount) {
            $this->error("❌ Cuenta de Facebook no encontrada con ID: {$facebookAccountId}");
            return Command::FAILURE;
        }

        $this->info("📱 **Cuenta de Facebook:**");
        $this->line("• Nombre: {$facebookAccount->account_name}");
        $this->line("• App ID: {$facebookAccount->app_id}");
        $this->newLine();

        // Verificar información de la cuenta publicitaria
        $this->checkAdAccountInfo($facebookAccount, $adAccountId);
        
        // Verificar restricciones
        $this->checkAdAccountRestrictions($facebookAccount, $adAccountId);
        
        // Verificar opciones de facturación disponibles
        $this->checkBillingOptions($facebookAccount, $adAccountId);

        return Command::SUCCESS;
    }

    private function checkAdAccountInfo(FacebookAccount $facebookAccount, string $adAccountId): void
    {
        $this->info("📊 **Información de la Cuenta Publicitaria:**");
        $this->line("=" . str_repeat("=", 40));

        $response = Http::get("https://graph.facebook.com/v18.0/{$adAccountId}", [
            'access_token' => $facebookAccount->access_token,
            'fields' => 'id,name,account_status,currency,timezone_name,created_time,age,amount_spent,balance,capabilities,disable_reason,min_campaign_group_spend_cap,min_daily_budget,spend_cap'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            $this->line("• ID: {$data['id']}");
            $this->line("• Nombre: {$data['name']}");
            $this->line("• Estado: {$data['account_status']}");
            $this->line("• Moneda: {$data['currency']}");
            $this->line("• Zona Horaria: {$data['timezone_name']}");
            $this->line("• Creada: {$data['created_time']}");
            $this->line("• Edad: {$data['age']} días");
            $this->line("• Gasto Total: \${$data['amount_spent']}");
            $this->line("• Balance: \${$data['balance']}");
            $this->line("• Presupuesto Mínimo Diario: \${$data['min_daily_budget']}");
            
            if (isset($data['capabilities'])) {
                $this->line("• Capacidades: " . implode(', ', $data['capabilities']));
            }
            
            if (isset($data['disable_reason'])) {
                $this->warn("⚠️ Razón de deshabilitación: {$data['disable_reason']}");
            }
            
        } else {
            $this->error("❌ Error obteniendo información: " . $response->body());
        }
        
        $this->newLine();
    }

    private function checkAdAccountRestrictions(FacebookAccount $facebookAccount, string $adAccountId): void
    {
        $this->info("🚫 **Restricciones de la Cuenta:**");
        $this->line("=" . str_repeat("=", 30));

        $response = Http::get("https://graph.facebook.com/v18.0/{$adAccountId}/restrictions", [
            'access_token' => $facebookAccount->access_token
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            if (empty($data['data'])) {
                $this->info("✅ No hay restricciones activas");
            } else {
                foreach ($data['data'] as $restriction) {
                    $this->warn("⚠️ Restricción: {$restriction['reason']}");
                    if (isset($restriction['description'])) {
                        $this->line("   Descripción: {$restriction['description']}");
                    }
                }
            }
        } else {
            $this->error("❌ Error obteniendo restricciones: " . $response->body());
        }
        
        $this->newLine();
    }

    private function checkBillingOptions(FacebookAccount $facebookAccount, string $adAccountId): void
    {
        $this->info("💳 **Opciones de Facturación Disponibles:**");
        $this->line("=" . str_repeat("=", 35));

        // Verificar eventos de facturación disponibles
        $billingEvents = ['IMPRESSIONS', 'CLICKS', 'LINK_CLICKS', 'POST_ENGAGEMENT', 'PURCHASE', 'APP_INSTALLS'];
        
        foreach ($billingEvents as $event) {
            $this->line("🔍 Probando evento: {$event}");
            
            // Crear un conjunto de anuncios de prueba (sin guardar)
            $testData = [
                'name' => 'Test Billing Event - ' . $event,
                'campaign_id' => 'test', // Esto fallará pero nos dirá si el evento es válido
                'optimization_goal' => 'REACH',
                'billing_event' => $event,
                'daily_budget' => 100, // $1 USD en centavos
                'targeting' => json_encode(['geo_locations' => ['countries' => ['VE']]]),
                'status' => 'PAUSED'
            ];
            
            $response = Http::post("https://graph.facebook.com/v18.0/{$adAccountId}/adsets", [
                'access_token' => $facebookAccount->access_token,
                'name' => $testData['name'],
                'campaign_id' => $testData['campaign_id'],
                'optimization_goal' => $testData['optimization_goal'],
                'billing_event' => $testData['billing_event'],
                'daily_budget' => $testData['daily_budget'],
                'targeting' => $testData['targeting'],
                'status' => $testData['status']
            ]);
            
            if ($response->successful()) {
                $this->info("   ✅ {$event} - DISPONIBLE");
            } else {
                $error = $response->json();
                if (isset($error['error']['error_subcode']) && $error['error']['error_subcode'] == 2446404) {
                    $this->warn("   ❌ {$event} - NO DISPONIBLE (cuenta nueva)");
                } else {
                    $this->line("   ⚠️ {$event} - Error: " . ($error['error']['message'] ?? 'Desconocido'));
                }
            }
        }
        
        $this->newLine();
    }
}
