<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CampaignReconciliationService;
use App\Models\AdvertisingPlan;
use App\Models\CampaignReconciliation;
use App\Models\AccountingTransaction;

class TestCampaignReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:campaign-reconciliation {--create-plans : Crear planes de ejemplo} {--detect : Ejecutar detección automática}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el sistema de conciliación automática de campañas de ADMETRICAS.COM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 **SISTEMA DE CONCILIACIÓN AUTOMÁTICA ADMETRICAS.COM**');
        $this->newLine();

        if ($this->option('create-plans')) {
            $this->createSamplePlans();
        }

        if ($this->option('detect')) {
            $this->testDetection();
        }

        if (!$this->option('create-plans') && !$this->option('detect')) {
            $this->showMenu();
        }

        return Command::SUCCESS;
    }

    /**
     * Mostrar menú interactivo
     */
    private function showMenu(): void
    {
        $this->info('📋 **MENÚ DE OPCIONES:**');
        $this->newLine();
        $this->line('1. Crear planes de publicidad de ejemplo');
        $this->line('2. Ejecutar detección automática de campañas');
        $this->line('3. Mostrar estadísticas del sistema');
        $this->line('4. Salir');
        $this->newLine();

        $choice = $this->ask('Selecciona una opción (1-4)');

        switch ($choice) {
            case '1':
                $this->createSamplePlans();
                break;
            case '2':
                $this->testDetection();
                break;
            case '3':
                $this->showSystemStats();
                break;
            case '4':
                $this->info('¡Hasta luego! 👋');
                break;
            default:
                $this->error('Opción inválida');
                break;
        }
    }

    /**
     * Crear planes de publicidad de ejemplo
     */
    private function createSamplePlans(): void
    {
        $this->info('📝 **CREANDO PLANES DE PUBLICIDAD DE EJEMPLO...**');
        $this->newLine();

        $plans = [
            [
                'plan_name' => 'Plan Básico 7 Días',
                'description' => 'Plan básico de publicidad por 7 días',
                'daily_budget' => 3.00,
                'duration_days' => 7,
                'total_budget' => 21.00,
                'client_price' => 29.00,
                'profit_margin' => 8.00,
                'profit_percentage' => 38.10,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reporte básico']
            ],
            [
                'plan_name' => 'Plan Premium 14 Días',
                'description' => 'Plan premium de publicidad por 14 días',
                'daily_budget' => 5.00,
                'duration_days' => 14,
                'total_budget' => 70.00,
                'client_price' => 99.00,
                'profit_margin' => 29.00,
                'profit_percentage' => 41.43,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reporte detallado', 'Optimización automática']
            ],
            [
                'plan_name' => 'Plan Empresarial 30 Días',
                'description' => 'Plan empresarial de publicidad por 30 días',
                'daily_budget' => 10.00,
                'duration_days' => 30,
                'total_budget' => 300.00,
                'client_price' => 399.00,
                'profit_margin' => 99.00,
                'profit_percentage' => 33.00,
                'features' => ['Facebook Ads', 'Instagram Ads', 'Reporte ejecutivo', 'Optimización avanzada', 'Soporte prioritario']
            ]
        ];

        foreach ($plans as $planData) {
            $existingPlan = AdvertisingPlan::where('plan_name', $planData['plan_name'])->first();
            
            if ($existingPlan) {
                $this->line("⚠️  Plan '{$planData['plan_name']}' ya existe");
                continue;
            }

            $plan = AdvertisingPlan::create($planData);
            $this->line("✅ Plan '{$plan->plan_name}' creado exitosamente");
            $this->line("   💰 Presupuesto diario: $" . number_format($plan->daily_budget, 2));
            $this->line("   📅 Duración: {$plan->duration_days} días");
            $this->line("   💵 Precio cliente: $" . number_format($plan->client_price, 2));
            $this->line("   🎯 Ganancia: $" . number_format($plan->profit_margin, 2));
        }

        $this->newLine();
        $this->info('🎉 **PLANES CREADOS EXITOSAMENTE!**');
        $this->newLine();
    }

    /**
     * Probar detección automática
     */
    private function testDetection(): void
    {
        $this->info('🔍 **PROBANDO DETECCIÓN AUTOMÁTICA DE CAMPAÑAS...**');
        $this->newLine();

        $service = new CampaignReconciliationService();
        
        try {
            $results = $service->detectAndReconcileCampaigns();
            
            $this->line("📊 **RESULTADOS DE LA DETECCIÓN:**");
            $this->line("   🎯 Campañas detectadas: {$results['detected']}");
            $this->line("   ✅ Campañas conciliadas: {$results['reconciled']}");
            $this->line("   ❌ Errores: " . count($results['errors']));
            
            if (!empty($results['details'])) {
                $this->newLine();
                $this->line("📋 **DETALLES:**");
                foreach ($results['details'] as $detail) {
                    $this->line("   • {$detail}");
                }
            }
            
            if (!empty($results['errors'])) {
                $this->newLine();
                $this->line("❌ **ERRORES:**");
                foreach ($results['errors'] as $error) {
                    $this->line("   • {$error}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error en la detección: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Mostrar estadísticas del sistema
     */
    private function showSystemStats(): void
    {
        $this->info('📊 **ESTADÍSTICAS DEL SISTEMA ADMETRICAS.COM**');
        $this->newLine();

        // Estadísticas de planes
        $totalPlans = AdvertisingPlan::count();
        $activePlans = AdvertisingPlan::active()->count();
        
        $this->line("📋 **PLANES DE PUBLICIDAD:**");
        $this->line("   Total: {$totalPlans}");
        $this->line("   Activos: {$activePlans}");
        $this->line("   Inactivos: " . ($totalPlans - $activePlans));

        // Estadísticas de conciliaciones
        $totalReconciliations = CampaignReconciliation::count();
        $pendingReconciliations = CampaignReconciliation::pending()->count();
        $activeReconciliations = CampaignReconciliation::active()->count();
        $completedReconciliations = CampaignReconciliation::completed()->count();

        $this->newLine();
        $this->line("🔄 **CONCILIACIONES DE CAMPAÑAS:**");
        $this->line("   Total: {$totalReconciliations}");
        $this->line("   Pendientes: {$pendingReconciliations}");
        $this->line("   Activas: {$activeReconciliations}");
        $this->line("   Completadas: {$completedReconciliations}");

        // Estadísticas de transacciones
        $totalTransactions = AccountingTransaction::count();
        $incomeTransactions = AccountingTransaction::income()->completed()->sum('amount');
        $expenseTransactions = AccountingTransaction::expense()->completed()->sum('amount');
        $profitTransactions = AccountingTransaction::profit()->completed()->sum('amount');

        $this->newLine();
        $this->line("💰 **TRANSACCIONES CONTABLES:**");
        $this->line("   Total transacciones: {$totalTransactions}");
        $this->line("   Ingresos totales: $" . number_format($incomeTransactions, 2));
        $this->line("   Gastos totales: $" . number_format($expenseTransactions, 2));
        $this->line("   Ganancias totales: $" . number_format($profitTransactions, 2));

        // Mostrar planes específicos
        if ($totalPlans > 0) {
            $this->newLine();
            $this->line("📋 **PLANES DISPONIBLES:**");
            
            $plans = AdvertisingPlan::all();
            $headers = ['ID', 'Nombre', 'Presupuesto Diario', 'Duración', 'Precio Cliente', 'Ganancia'];
            $rows = [];
            
            foreach ($plans as $plan) {
                $rows[] = [
                    $plan->id,
                    $plan->plan_name,
                    '$' . number_format($plan->daily_budget, 2),
                    $plan->duration_days . ' días',
                    '$' . number_format($plan->client_price, 2),
                    '$' . number_format($plan->profit_margin, 2)
                ];
            }
            
            $this->table($headers, $rows);
        }

        $this->newLine();
    }
}
