<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdvertisingPlan;

class AdvertisingPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creando planes de publicidad reales de ADMETRICAS.COM...');

        // Limpiar planes existentes
        AdvertisingPlan::truncate();

        $plans = [
            // PLANES DE $1 DIARIO
            [
                'plan_name' => 'Plan Básico 3 Días',
                'description' => 'Plan de $1 diarios por 3 días',
                'daily_budget' => 1.00,
                'duration_days' => 3,
                'total_budget' => 3.00,
                'client_price' => 6.00,
                'profit_margin' => 3.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 4 Días',
                'description' => 'Plan de $1 diarios por 4 días',
                'daily_budget' => 1.00,
                'duration_days' => 4,
                'total_budget' => 4.00,
                'client_price' => 8.00,
                'profit_margin' => 4.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 5 Días',
                'description' => 'Plan de $1 diarios por 5 días',
                'daily_budget' => 1.00,
                'duration_days' => 5,
                'total_budget' => 5.00,
                'client_price' => 10.00,
                'profit_margin' => 5.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 6 Días',
                'description' => 'Plan de $1 diarios por 6 días',
                'daily_budget' => 1.00,
                'duration_days' => 6,
                'total_budget' => 6.00,
                'client_price' => 12.00,
                'profit_margin' => 6.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 7 Días',
                'description' => 'Plan de $1 diarios por 7 días',
                'daily_budget' => 1.00,
                'duration_days' => 7,
                'total_budget' => 7.00,
                'client_price' => 14.00,
                'profit_margin' => 7.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 8 Días',
                'description' => 'Plan de $1 diarios por 8 días',
                'daily_budget' => 1.00,
                'duration_days' => 8,
                'total_budget' => 8.00,
                'client_price' => 16.00,
                'profit_margin' => 8.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 9 Días',
                'description' => 'Plan de $1 diarios por 9 días',
                'daily_budget' => 1.00,
                'duration_days' => 9,
                'total_budget' => 9.00,
                'client_price' => 18.00,
                'profit_margin' => 9.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 10 Días',
                'description' => 'Plan de $1 diarios por 10 días',
                'daily_budget' => 1.00,
                'duration_days' => 10,
                'total_budget' => 10.00,
                'client_price' => 20.00,
                'profit_margin' => 10.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],
            [
                'plan_name' => 'Plan Básico 15 Días',
                'description' => 'Plan de $1 diarios por 15 días',
                'daily_budget' => 1.00,
                'duration_days' => 15,
                'total_budget' => 15.00,
                'client_price' => 30.00,
                'profit_margin' => 15.00,
                'profit_percentage' => 100.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes básicos'],
            ],

            // PLANES DE $2 DIARIO
            [
                'plan_name' => 'Plan Intermedio 3 Días',
                'description' => 'Plan de $2 diarios por 3 días',
                'daily_budget' => 2.00,
                'duration_days' => 3,
                'total_budget' => 6.00,
                'client_price' => 11.00,
                'profit_margin' => 5.00,
                'profit_percentage' => 83.3,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 4 Días',
                'description' => 'Plan de $2 diarios por 4 días',
                'daily_budget' => 2.00,
                'duration_days' => 4,
                'total_budget' => 8.00,
                'client_price' => 13.00,
                'profit_margin' => 5.00,
                'profit_percentage' => 62.5,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 5 Días',
                'description' => 'Plan de $2 diarios por 5 días',
                'daily_budget' => 2.00,
                'duration_days' => 5,
                'total_budget' => 10.00,
                'client_price' => 15.00,
                'profit_margin' => 5.00,
                'profit_percentage' => 50.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 6 Días',
                'description' => 'Plan de $2 diarios por 6 días',
                'daily_budget' => 2.00,
                'duration_days' => 6,
                'total_budget' => 12.00,
                'client_price' => 18.00,
                'profit_margin' => 6.00,
                'profit_percentage' => 50.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 7 Días',
                'description' => 'Plan de $2 diarios por 7 días',
                'daily_budget' => 2.00,
                'duration_days' => 7,
                'total_budget' => 14.00,
                'client_price' => 20.00,
                'profit_margin' => 6.00,
                'profit_percentage' => 42.9,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 8 Días',
                'description' => 'Plan de $2 diarios por 8 días',
                'daily_budget' => 2.00,
                'duration_days' => 8,
                'total_budget' => 16.00,
                'client_price' => 24.00,
                'profit_margin' => 8.00,
                'profit_percentage' => 50.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 9 Días',
                'description' => 'Plan de $2 diarios por 9 días',
                'daily_budget' => 2.00,
                'duration_days' => 9,
                'total_budget' => 18.00,
                'client_price' => 26.00,
                'profit_margin' => 8.00,
                'profit_percentage' => 44.4,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 10 Días',
                'description' => 'Plan de $2 diarios por 10 días',
                'daily_budget' => 2.00,
                'duration_days' => 10,
                'total_budget' => 20.00,
                'client_price' => 32.00,
                'profit_margin' => 12.00,
                'profit_percentage' => 60.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],
            [
                'plan_name' => 'Plan Intermedio 15 Días',
                'description' => 'Plan de $2 diarios por 15 días',
                'daily_budget' => 2.00,
                'duration_days' => 15,
                'total_budget' => 30.00,
                'client_price' => 45.00,
                'profit_margin' => 15.00,
                'profit_percentage' => 50.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes avanzados', 'Optimización'],
            ],

            // PLANES DE $3 DIARIO
            [
                'plan_name' => 'Plan Premium 3 Días',
                'description' => 'Plan de $3 diarios por 3 días',
                'daily_budget' => 3.00,
                'duration_days' => 3,
                'total_budget' => 9.00,
                'client_price' => 15.00,
                'profit_margin' => 6.00,
                'profit_percentage' => 66.7,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 4 Días',
                'description' => 'Plan de $3 diarios por 4 días',
                'daily_budget' => 3.00,
                'duration_days' => 4,
                'total_budget' => 12.00,
                'client_price' => 18.00,
                'profit_margin' => 6.00,
                'profit_percentage' => 50.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 5 Días',
                'description' => 'Plan de $3 diarios por 5 días',
                'daily_budget' => 3.00,
                'duration_days' => 5,
                'total_budget' => 15.00,
                'client_price' => 22.00,
                'profit_margin' => 7.00,
                'profit_percentage' => 46.7,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 6 Días',
                'description' => 'Plan de $3 diarios por 6 días',
                'daily_budget' => 3.00,
                'duration_days' => 6,
                'total_budget' => 18.00,
                'client_price' => 25.00,
                'profit_margin' => 7.00,
                'profit_percentage' => 38.9,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 7 Días',
                'description' => 'Plan de $3 diarios por 7 días',
                'daily_budget' => 3.00,
                'duration_days' => 7,
                'total_budget' => 21.00,
                'client_price' => 29.00,
                'profit_margin' => 8.00,
                'profit_percentage' => 38.1,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 8 Días',
                'description' => 'Plan de $3 diarios por 8 días',
                'daily_budget' => 3.00,
                'duration_days' => 8,
                'total_budget' => 24.00,
                'client_price' => 32.00,
                'profit_margin' => 8.00,
                'profit_percentage' => 33.3,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 9 Días',
                'description' => 'Plan de $3 diarios por 9 días',
                'daily_budget' => 3.00,
                'duration_days' => 9,
                'total_budget' => 27.00,
                'client_price' => 36.00,
                'profit_margin' => 9.00,
                'profit_percentage' => 33.3,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 10 Días',
                'description' => 'Plan de $3 diarios por 10 días',
                'daily_budget' => 3.00,
                'duration_days' => 10,
                'total_budget' => 30.00,
                'client_price' => 39.00,
                'profit_margin' => 9.00,
                'profit_percentage' => 30.0,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
            [
                'plan_name' => 'Plan Premium 15 Días',
                'description' => 'Plan de $3 diarios por 15 días',
                'daily_budget' => 3.00,
                'duration_days' => 15,
                'total_budget' => 45.00,
                'client_price' => 57.00,
                'profit_margin' => 12.00,
                'profit_percentage' => 26.7,
                'is_active' => true,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reportes premium', 'Optimización avanzada', 'Soporte prioritario'],
            ],
        ];

        $created = 0;
        foreach ($plans as $planData) {
            try {
                AdvertisingPlan::create($planData);
                $created++;
                $this->command->info("✅ Creado: {$planData['plan_name']} - {$planData['daily_budget']}$ diarios por {$planData['duration_days']} días = {$planData['total_budget']}$ presupuesto, precio cliente {$planData['client_price']}$, ganancia {$planData['profit_margin']}$ ({$planData['profit_percentage']}%)");
            } catch (\Exception $e) {
                $this->command->error("❌ Error creando {$planData['plan_name']}: " . $e->getMessage());
            }
        }

        $this->command->info("🎉 ¡SEEDER COMPLETADO! Se crearon {$created} planes de publicidad.");
        $this->command->info("📊 Total de planes: " . count($plans));
        
        // Mostrar resumen por categoría
        $basicPlans = AdvertisingPlan::where('daily_budget', 1.00)->count();
        $intermediatePlans = AdvertisingPlan::where('daily_budget', 2.00)->count();
        $premiumPlans = AdvertisingPlan::where('daily_budget', 3.00)->count();
        
        $this->command->info("📋 Resumen por categoría:");
        $this->command->info("   • Planes Básicos ($1 diario): {$basicPlans}");
        $this->command->info("   • Planes Intermedios ($2 diario): {$intermediatePlans}");
        $this->command->info("   • Planes Premium ($3 diario): {$premiumPlans}");
    }
}
