<?php

namespace App\Console\Commands;

use App\Models\FacebookAccount;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestFacebookConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facebook:test-connection {--account-id= : ID específico de la cuenta}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la conexión con Facebook Ads API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando conexión con Facebook Ads API...');
        
        try {
            // Obtener cuenta de Facebook
            $accountId = $this->option('account-id');
            
            if ($accountId) {
                $fbAccount = FacebookAccount::where('account_id', $accountId)->first();
            } else {
                $fbAccount = FacebookAccount::first();
            }
            
            if (!$fbAccount) {
                $this->error('❌ No se encontró ninguna cuenta de Facebook configurada.');
                $this->info('💡 Para configurar una cuenta:');
                $this->info('   1. Ve a Facebook Ads Manager');
                $this->info('   2. Crea una cuenta publicitaria');
                $this->info('   3. Configura la cuenta en el panel de administración');
                return 1;
            }
            
            $this->info("📱 Usando cuenta: {$fbAccount->account_name}");
            $this->info("🆔 Account ID: {$fbAccount->account_id}");
            $this->info("📱 App ID: {$fbAccount->app_id}");
            
            // Inicializar Facebook API
            $this->info('🔧 Inicializando Facebook API...');
            Api::init(
                $fbAccount->app_id,
                $fbAccount->app_secret,
                $fbAccount->access_token
            );
            
            $this->info('✅ Facebook API inicializada correctamente');
            
            // Crear objeto AdAccount
            $account = new AdAccount('act_' . $fbAccount->account_id);
            
            // Probar obtención de información básica de la cuenta
            $this->info('📊 Obteniendo información de la cuenta...');
            $accountInfo = $account->read(['name', 'account_status', 'currency']);
            
            $this->info("✅ Conexión exitosa!");
            $this->info("📋 Nombre de la cuenta: {$accountInfo->name}");
            $this->info("📊 Estado de la cuenta: {$accountInfo->account_status}");
            $this->info("💰 Moneda: {$accountInfo->currency}");
            
            // Probar obtención de insights básicos
            $this->info('📈 Probando obtención de insights...');
            $fields = ['campaign_name', 'impressions', 'clicks', 'spend'];
            $params = [
                'level' => 'campaign',
                'time_range' => [
                    'since' => date('Y-m-d', strtotime('-7 days')),
                    'until' => date('Y-m-d'),
                ],
                'limit' => 5, // Solo 5 campañas para la prueba
            ];
            
            $insights = $account->getInsights($fields, $params);
            $count = count($insights);
            
            $this->info("✅ Se encontraron {$count} campañas con datos");
            
            if ($count > 0) {
                $this->info('📊 Muestra de datos obtenidos:');
                $this->table(
                    ['Campaña', 'Impresiones', 'Clicks', 'Gasto'],
                    array_map(function($insight) {
                        return [
                            $insight->campaign_name ?? 'Sin nombre',
                            number_format($insight->impressions ?? 0),
                            number_format($insight->clicks ?? 0),
                            '$' . number_format($insight->spend ?? 0, 2)
                        ];
                    }, array_slice($insights->getArrayCopy(), 0, 3))
                );
            }
            
            $this->info('🎉 ¡Todas las pruebas pasaron exitosamente!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la prueba:');
            $this->error($e->getMessage());
            
            Log::error('Error en prueba de conexión Facebook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}
