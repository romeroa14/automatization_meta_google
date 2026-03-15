<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TokenRenewalService;
use App\Models\FacebookAccount;

class RenewFacebookTokens extends Command
{
    protected $signature = 'facebook:renew-tokens {--check : Solo verificar estado sin renovar}';
    protected $description = 'Renovar tokens de Facebook que estén próximos a expirar';

    public function handle()
    {
        $checkOnly = $this->option('check');
        
        $this->info("🔄 **RENOVACIÓN DE TOKENS DE FACEBOOK**");
        $this->newLine();

        $tokenService = new TokenRenewalService();
        $facebookAccounts = FacebookAccount::where('is_active', true)->get();

        if ($facebookAccounts->isEmpty()) {
            $this->warn("⚠️ No hay cuentas de Facebook activas configuradas.");
            return Command::SUCCESS;
        }

        $this->info("📱 **Cuentas de Facebook encontradas:** {$facebookAccounts->count()}");
        $this->newLine();

        foreach ($facebookAccounts as $account) {
            $this->info("🔍 **Verificando cuenta:** {$account->account_name}");
            $this->line("• App ID: {$account->app_id}");
            $this->line("• Token: {$account->masked_access_token}");
            
            if ($account->token_expires_at) {
                $this->line("• Expira: {$account->token_expires_at->format('Y-m-d H:i:s')}");
                $this->line("• Días restantes: " . $account->token_expires_at->diffInDays(now()));
            } else {
                $this->line("• Expira: No especificado");
            }

            // Verificar estado del token
            $status = $tokenService->checkTokenStatus($account);
            
            if ($status['valid']) {
                $this->info("✅ Token válido");
                $this->line("• Usuario: {$status['user_name']} (ID: {$status['user_id']})");
                
                if ($status['needs_renewal']) {
                    $this->warn("⚠️ Token necesita renovación");
                    
                    if (!$checkOnly) {
                        $this->info("🔄 Renovando token...");
                        $result = $tokenService->renewLongLivedToken($account);
                        
                        if ($result['success']) {
                            $this->info("✅ Token renovado exitosamente");
                            $this->line("• Nuevo token: {$result['access_token']}");
                            $this->line("• Expira: {$result['expires_at']}");
                        } else {
                            $this->error("❌ Error renovando token: {$result['error']}");
                        }
                    }
                } else {
                    $this->info("✅ Token no necesita renovación");
                }
            } else {
                $this->error("❌ Token inválido: {$status['error']}");
                
                if (!$checkOnly) {
                    $this->warn("⚠️ Intenta renovar el token manualmente desde el Administrador de Anuncios");
                }
            }
            
            $this->newLine();
        }

        if ($checkOnly) {
            $this->info("🔍 **Modo verificación completado**");
            $this->line("Usa el comando sin --check para renovar tokens automáticamente");
        } else {
            $this->info("🎉 **Proceso de renovación completado**");
        }

        return Command::SUCCESS;
    }
}
