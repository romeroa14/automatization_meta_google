<?php

namespace Database\Seeders;

use App\Models\FacebookAccount;
use App\Models\GoogleSheet;
use App\Models\AutomationTask;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear cuenta de Facebook
        $facebookAccount = FacebookAccount::create([
            'account_name' => 'SKYTEX',
            'account_id' => '658326730301827',
            'app_id' => '603275022244128',
            'app_secret' => 'tu_app_secret_aqui',
            'access_token' => 'tu_access_token_aqui',
            'is_active' => true,
        ]);

        // Crear Google Sheet
        $googleSheet = GoogleSheet::create([
            'name' => 'SKYTEX - AGOSTO',
            'spreadsheet_id' => '1eSSLrhmiiHt6nQTS24yZ-QFPumMvw0HhdjqxoNd7HGg',
            'worksheet_name' => 'BRANDS SHOP',
            'cell_mapping' => [
                'impressions' => 'B2',
                'clicks' => 'B3',
                'spend' => 'B4',
                'reach' => 'B5',
                'ctr' => 'B6',
                'cpm' => 'B7',
                'cpc' => 'B8',
            ],
            'is_active' => true,
        ]);

        // Crear tarea de automatización
        $automationTask = AutomationTask::create([
            'name' => 'Colocar metricas en sheets',
            'description' => 'Sincroniza métricas de Facebook Ads a Google Sheets',
            'facebook_account_id' => $facebookAccount->id,
            'google_sheet_id' => $googleSheet->id,
            'frequency' => 'daily',
            'scheduled_time' => '09:00:00',
            'is_active' => true,
        ]);

        $this->command->info('✅ Datos iniciales creados exitosamente:');
        $this->command->info("📱 Facebook Account: {$facebookAccount->account_name}");
        $this->command->info("📊 Google Sheet: {$googleSheet->name}");
        $this->command->info("⚙️ Automation Task: {$automationTask->name}");
        $this->command->info("🌐 Usando Web App Universal desde variables de entorno");
    }
}
