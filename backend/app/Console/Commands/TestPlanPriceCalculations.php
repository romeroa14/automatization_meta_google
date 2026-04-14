<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Models\AdvertisingPlan;
use Illuminate\Console\Command;

class TestPlanPriceCalculations extends Command
{
    protected $signature = 'test:plan-prices';
    protected $description = 'Probar cálculos de precios de planes en ambas tasas';

    public function handle()
    {
        $this->info('🧮 PROBANDO CÁLCULOS DE PRECIOS DE PLANES');
        $this->line('');

        // Verificar que las tasas estén disponibles
        $binanceRate = ExchangeRate::getLatestRate('USD', 'BINANCE');
        $bcvRate = ExchangeRate::getLatestRate('USD', 'BCV');

        if (!$binanceRate || !$bcvRate) {
            $this->error('❌ No se pudieron obtener las tasas de cambio');
            return;
        }

        $this->info('📊 TASAS ACTUALES:');
        $this->line("   BCV:     " . number_format($bcvRate->rate, 2, ',', '.') . " Bs.");
        $this->line("   Binance: " . number_format($binanceRate->rate, 2, ',', '.') . " Bs.");
        $this->line("   Diferencia: " . number_format($binanceRate->rate - $bcvRate->rate, 2, ',', '.') . " Bs.");
        $this->line('');

        // Probar con diferentes precios de planes
        $testPrices = [1, 3, 6, 10, 15, 20, 30];

        $this->info('💰 CÁLCULOS DE PRECIOS DE PLANES:');
        $this->line('');

        foreach ($testPrices as $usdPrice) {
            $equivalents = ExchangeRate::calculatePlanPriceEquivalents($usdPrice);
            
            if ($equivalents) {
                $this->line("Plan de \${$usdPrice} USD:");
                $this->line("   Precio en Binance: " . number_format($equivalents['binance_price'], 2, ',', '.') . " Bs.");
                $this->line("   Precio en BCV:     " . number_format($equivalents['bcv_price'], 2, ',', '.') . " Bs.");
                $this->line("   Factor conversión: " . number_format($equivalents['conversion_factor'], 3) . "x");
                $this->line('');
            }
        }

        // Probar con planes existentes
        $this->info('📋 PRECIOS DE PLANES EXISTENTES:');
        $this->line('');

        $plans = AdvertisingPlan::take(5)->get();
        
        foreach ($plans as $plan) {
            $equivalents = ExchangeRate::calculatePlanPriceEquivalents($plan->total_budget);
            
            if ($equivalents) {
                $this->line("{$plan->plan_name} (Presupuesto: \${$plan->total_budget}):");
                $this->line("   Precio cliente actual: \${$plan->client_price}");
                $this->line("   Precio en Binance: " . number_format($equivalents['binance_price'], 2, ',', '.') . " Bs.");
                $this->line("   Precio en BCV:     " . number_format($equivalents['bcv_price'], 2, ',', '.') . " Bs.");
                $this->line('');
            }
        }

        // Estadísticas generales
        $stats = ExchangeRate::getPlanPriceStatistics();
        if (!empty($stats)) {
            $this->info('📈 ESTADÍSTICAS GENERALES:');
            $this->line("   Factor de conversión: " . number_format($stats['conversion_factor'], 3) . "x");
            $this->line("   Diferencia porcentual: " . number_format($stats['percentage_difference'], 1) . "%");
            $this->line("   Última actualización: " . $stats['last_updated']->format('d/m/Y H:i'));
        }

        $this->line('');
        $this->info('✅ Pruebas completadas exitosamente');
    }
}