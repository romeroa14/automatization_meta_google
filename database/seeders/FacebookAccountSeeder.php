<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FacebookAccount;

class FacebookAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear/actualizar cuenta principal (desarrollo)
        $this->createOrUpdateAccount(
            '738576925677923',
            'ADMETRICAS.COM - Cuenta Principal',
            '78f022c605d18b045bf85f73460516e2',
            'EAAKfu1dLWWMBPTGJ97yqnUqnDoa0iC7xson0ZCgJgO86aPAJav8Wav3TJXuGEUV6vxh8zo5lOAOLZANCtr0uaTPExyNzwk6Deeb3No2vPaNZAs6CH8X5kyHE9M7Gp38uRxTV6qkZAZBZC6wZC4mB46AJjtlLZCmEuSNTgFUYQ6ESNnpww0YZB0EIOUob5ZACSZBr9xnt9Fm0oC7AquBjgZDZD'
        );

        // Crear/actualizar cuenta activa (producción)
        $this->createOrUpdateAccount(
            '808947008240397',
            'TOKEN ADMETRICAS - App Activa',
            '570c6a1ab1ab8571b59a82f5088e46ca',
            'EAALfu6cRew0BPWUzqBszQdmByLldZCOXY6eZCFUyX5H9iPUHZBNik9CzEYd0EU9YIWc237o1AcFKq60t8Aw6TzKZBf0lA4fZCzMdAjgA7pRoRGVU2E7OgCZAezpEjRyCnbBk7vi3sCFhQkQW0RTVkajwBRzxFnoEUvLhUUlcIMejnFb9AyeCIR98fhHqCec6beksmIhb2JPAZDZD'
        );
        
        // Mostrar información de todas las cuentas
        $this->command->info("\n📊 Información de las cuentas:");
        
        $accounts = FacebookAccount::all();
        foreach ($accounts as $account) {
            $this->command->info("\n   🔹 {$account->account_name}:");
            $this->command->info("      • App ID: {$account->app_id}");
            $this->command->info("      • Access Token: " . substr($account->access_token, 0, 20) . "...");
            $this->command->info("      • Estado: " . ($account->is_active ? 'Activa' : 'Inactiva'));
        }
        
        $this->command->info("\n🔧 Próximos pasos:");
        $this->command->info("   1. Configurar selected_ad_account_id en el panel de administración");
        $this->command->info("   2. Configurar selected_page_id en el panel de administración");
        $this->command->info("   3. Probar la conexión con Meta API");
        $this->command->info("   4. Activar la cuenta 'TOKEN ADMETRICAS - App Activa' para producción");
    }

    private function createOrUpdateAccount($appId, $accountName, $appSecret, $accessToken)
    {
        $existingAccount = FacebookAccount::where('app_id', $appId)->first();
        
        $accountData = [
            'account_name' => $accountName,
            'app_id' => $appId,
            'app_secret' => $appSecret,
            'access_token' => $accessToken,
            'token_expires_at' => $appId === '808947008240397' ? now()->addDays(60) : null, // Token de larga duración para app activa
            'is_active' => $appId === '808947008240397', // Activar solo la app de producción
            'settings' => [
                'auto_sync' => true,
                'sync_frequency' => 'daily',
                'notifications' => true,
                'default_currency' => 'USD',
                'timezone' => 'America/Caracas'
            ],
            'selected_ad_account_id' => null,
            'selected_page_id' => null,
            'selected_campaign_ids' => null,
            'selected_ad_ids' => null
        ];
        
        if ($existingAccount) {
            $existingAccount->update($accountData);
            $this->command->info("✅ Facebook Account actualizada: {$accountName}");
        } else {
            FacebookAccount::create($accountData);
            $this->command->info("✅ Facebook Account creada: {$accountName}");
        }
    }
}