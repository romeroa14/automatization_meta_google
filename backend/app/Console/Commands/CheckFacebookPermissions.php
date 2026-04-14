<?php

namespace App\Console\Commands;

use App\Models\FacebookAccount;
use FacebookAds\Api;
use FacebookAds\Object\AdAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckFacebookPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facebook:check-permissions {--account-id= : ID específico de la cuenta}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica los permisos de Facebook Ads API y proporciona orientación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando permisos de Facebook Ads API...');
        
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
                $this->showSetupInstructions();
                return 1;
            }
            
            $this->info("📱 Verificando cuenta: {$fbAccount->account_name}");
            $this->info("🆔 Account ID: {$fbAccount->account_id}");
            
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
            
            // Verificar información básica de la cuenta
            $this->info('📊 Verificando información de la cuenta...');
            $accountInfo = $account->read(['name', 'account_status', 'currency', 'timezone_name']);
            
            $this->info("✅ Cuenta encontrada: {$accountInfo->name}");
            $this->info("📊 Estado: {$accountInfo->account_status}");
            $this->info("💰 Moneda: {$accountInfo->currency}");
            $this->info("🌍 Zona horaria: {$accountInfo->timezone_name}");
            
            // Verificar permisos intentando obtener insights
            $this->info('🔐 Verificando permisos de ads_management...');
            
            try {
                $fields = ['campaign_name'];
                $params = [
                    'level' => 'campaign',
                    'time_range' => [
                        'since' => date('Y-m-d', strtotime('-1 day')),
                        'until' => date('Y-m-d'),
                    ],
                    'limit' => 1,
                ];
                
                $insights = $account->getInsights($fields, $params);
                $count = count($insights);
                
                if ($count > 0) {
                    $this->info('✅ Permisos de ads_management: ACTIVOS');
                    $this->info("📈 Se encontraron {$count} campañas con datos");
                    $this->info('🎉 ¡La cuenta está lista para usar!');
                } else {
                    $this->warn('⚠️  Permisos activos pero no hay datos de campañas');
                    $this->info('💡 Esto puede ser normal si:');
                    $this->info('   • No tienes campañas activas');
                    $this->info('   • Las campañas no tienen datos en el rango de fechas');
                    $this->info('   • Las campañas están en estado inactivo');
                }
                
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                if (strpos($errorMessage, 'ads_management') !== false || 
                    strpos($errorMessage, 'ads_read') !== false ||
                    strpos($errorMessage, 'Ad account owner has NOT grant') !== false) {
                    
                    $this->error('❌ PERMISOS INSUFICIENTES');
                    $this->error($errorMessage);
                    $this->showPermissionInstructions();
                    
                } else {
                    $this->error('❌ Error inesperado:');
                    $this->error($errorMessage);
                }
                
                return 1;
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error durante la verificación:');
            $this->error($e->getMessage());
            
            Log::error('Error en verificación de permisos Facebook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    private function showSetupInstructions(): void
    {
        $this->newLine();
        $this->info('💡 INSTRUCCIONES DE CONFIGURACIÓN:');
        $this->newLine();
        $this->info('1️⃣ CREAR CUENTA PUBLICITARIA:');
        $this->info('   • Ve a: https://www.facebook.com/adsmanager');
        $this->info('   • Haz clic en "Crear cuenta publicitaria"');
        $this->info('   • Completa la información básica');
        $this->info('   • Agrega un método de pago');
        $this->newLine();
        $this->info('2️⃣ CONFIGURAR EN EL PANEL:');
        $this->info('   • Ve al panel de administración de la aplicación');
        $this->info('   • Agrega una nueva cuenta de Facebook');
        $this->info('   • Incluye el App ID, App Secret y Access Token');
        $this->newLine();
        $this->info('3️⃣ SOLICITAR PERMISOS:');
        $this->info('   • Ve a: https://developers.facebook.com/');
        $this->info('   • En tu app, solicita el permiso "ads_management"');
    }
    
    private function showPermissionInstructions(): void
    {
        $this->newLine();
        $this->warn('🔐 SOLUCIÓN PARA PERMISOS:');
        $this->newLine();
        $this->info('1️⃣ VERIFICAR CUENTA PUBLICITARIA:');
        $this->info('   • Asegúrate de que la cuenta publicitaria esté activa');
        $this->info('   • Verifica que tengas permisos de administrador');
        $this->info('   • Confirma que la cuenta no esté en estado suspendido');
        $this->newLine();
        $this->info('2️⃣ SOLICITAR PERMISOS EN DEVELOPERS:');
        $this->info('   • Ve a: https://developers.facebook.com/');
        $this->info('   • Selecciona tu aplicación');
        $this->info('   • Ve a "App Review" > "Permissions and Features"');
        $this->info('   • Solicita el permiso "ads_management"');
        $this->info('   • Haz una llamada de prueba a la API');
        $this->newLine();
        $this->info('3️⃣ ALTERNATIVA - CUENTAS DE PRUEBA:');
        $this->info('   • Solicita acceso a "Test Ad Accounts"');
        $this->info('   • Esto te dará cuentas de prueba para desarrollo');
        $this->info('   • No requiere aprobación de Facebook');
        $this->newLine();
        $this->info('💡 El proceso puede tomar hasta 24 horas después');
        $this->info('   de la primera llamada exitosa a la API.');
    }
}
