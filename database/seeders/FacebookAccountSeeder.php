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
        // Verificar si ya existe una cuenta con estos datos
        $existingAccount = FacebookAccount::where('app_id', '738576925677923')->first();
        
        if ($existingAccount) {
            $this->command->info('Facebook Account ya existe, actualizando datos...');
            
            $existingAccount->update([
                'account_name' => 'ADMETRICAS.COM - Cuenta Principal',
                'app_id' => '738576925677923',
                'app_secret' => '78f022c605d18b045bf85f73460516e2',
                'access_token' => 'EAAKfu1dLWWMBPTGJ97yqnUqnDoa0iC7xson0ZCgJgO86aPAJav8Wav3TJXuGEUV6vxh8zo5lOAOLZANCtr0uaTPExyNzwk6Deeb3No2vPaNZAs6CH8X5kyHE9M7Gp38uRxTV6qkZAZBZC6wZC4mB46AJjtlLZCmEuSNTgFUYQ6ESNnpww0YZB0EIOUob5ZACSZBr9xnt9Fm0oC7AquBjgZDZD',
                'is_active' => true,
                'settings' => [
                    'auto_sync' => true,
                    'sync_frequency' => 'daily',
                    'notifications' => true,
                    'default_currency' => 'USD',
                    'timezone' => 'America/Caracas'
                ],
                'selected_ad_account_id' => null, // Se configurará después
                'selected_page_id' => null, // Se configurará después
                'selected_campaign_ids' => null,
                'selected_ad_ids' => null
            ]);
            
            $this->command->info("✅ Facebook Account actualizada: {$existingAccount->account_name}");
        } else {
            $facebookAccount = FacebookAccount::create([
                'account_name' => 'ADMETRICAS.COM - Cuenta Principal',
                'app_id' => '738576925677923',
                'app_secret' => '78f022c605d18b045bf85f73460516e2',
                'access_token' => 'EAAKfu1dLWWMBPTGJ97yqnUqnDoa0iC7xson0ZCgJgO86aPAJav8Wav3TJXuGEUV6vxh8zo5lOAOLZANCtr0uaTPExyNzwk6Deeb3No2vPaNZAs6CH8X5kyHE9M7Gp38uRxTV6qkZAZBZC6wZC4mB46AJjtlLZCmEuSNTgFUYQ6ESNnpww0YZB0EIOUob5ZACSZBr9xnt9Fm0oC7AquBjgZDZD',
                'is_active' => true,
                'settings' => [
                    'auto_sync' => true,
                    'sync_frequency' => 'daily',
                    'notifications' => true,
                    'default_currency' => 'USD',
                    'timezone' => 'America/Caracas'
                ],
                'selected_ad_account_id' => null, // Se configurará después
                'selected_page_id' => null, // Se configurará después
                'selected_campaign_ids' => null,
                'selected_ad_ids' => null
            ]);
            
            $this->command->info("✅ Facebook Account creada: {$facebookAccount->account_name}");
        }
        
        // Mostrar información de la cuenta
        $account = FacebookAccount::where('app_id', '738576925677923')->first();
        $this->command->info("\n📊 Información de la cuenta:");
        $this->command->info("   • Nombre: {$account->account_name}");
        $this->command->info("   • App ID: {$account->app_id}");
        $this->command->info("   • Access Token: {$account->masked_access_token}");
        $this->command->info("   • Estado: {$account->status_label}");
        $this->command->info("   • Credenciales válidas: " . ($account->hasValidCredentials() ? 'Sí' : 'No'));
        $this->command->info("   • Puede automatizarse: " . ($account->canBeAutomated() ? 'Sí' : 'No'));
        
        $this->command->info("\n🔧 Próximos pasos:");
        $this->command->info("   1. Configurar selected_ad_account_id en el panel de administración");
        $this->command->info("   2. Configurar selected_page_id en el panel de administración");
        $this->command->info("   3. Probar la conexión con Meta API");
    }
}