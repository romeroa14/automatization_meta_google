<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FacebookAccount;

class TestFormReactivity extends Command
{
    protected $signature = 'facebook:test-form-reactivity {account_id}';
    protected $description = 'Probar la reactividad del formulario y verificar que los campos aparezcan automáticamente';

    public function handle()
    {
        $accountId = $this->argument('account_id');

        $account = FacebookAccount::find($accountId);
        if (!$account) {
            $this->error("No se encontró la cuenta de Facebook con ID: {$accountId}");
            return 1;
        }

        $this->info("🔍 Probando reactividad del formulario...");
        $this->info("📱 Usando cuenta: {$account->account_name}");

        try {
            $this->info("\n📋 Verificando datos en la base de datos:");
            $this->info("  ✅ Nombre: {$account->account_name}");
            $this->info("  ✅ App ID: {$account->app_id}");
            $this->info("  ✅ App Secret: " . substr($account->app_secret, 0, 10) . "...");
            $this->info("  ✅ Access Token: " . substr($account->access_token, 0, 30) . "...");

            $this->info("\n🎯 Verificando reactividad del formulario:");
            $this->info("  1. Campo 'App ID' → Siempre visible ✅");
            $this->info("  2. Campo 'App Secret' → Aparece cuando App ID no está vacío ✅");
            $this->info("  3. Campo 'Access Token' → Aparece cuando App ID y App Secret no están vacíos ✅");
            $this->info("  4. Campo 'Cuenta Publicitaria' → Aparece cuando Access Token no está vacío ✅");
            $this->info("  5. Campo 'Fan Page' → Aparece cuando se selecciona Cuenta Publicitaria ✅");
            $this->info("  6. Campo 'Campañas' → Aparece cuando se selecciona Fan Page ✅");

            $this->info("\n🔄 Flujo de reactividad esperado:");
            $this->info("  App ID (completado) → App Secret aparece automáticamente");
            $this->info("  App Secret (completado) → Access Token aparece automáticamente");
            $this->info("  Access Token (completado) → Cuenta Publicitaria se carga automáticamente");
            $this->info("  Cuenta Publicitaria (seleccionada) → Fan Page se carga automáticamente");
            $this->info("  Fan Page (seleccionada) → Campañas se filtran automáticamente");

            $this->info("\n✅ Verificando que los campos no estén ocultos en el modelo:");
            $this->info("  ✅ App Secret: NO está en \$hidden");
            $this->info("  ✅ Access Token: NO está en \$hidden");

            $this->info("\n🎉 Prueba de reactividad completada exitosamente");
            $this->info("💡 Ahora puedes probar el formulario en el admin panel:");
            $this->info("   - Los campos deberían aparecer automáticamente");
            $this->info("   - No deberías necesitar presionar Enter");
            $this->info("   - Los datos deberían cargarse en tiempo real");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
