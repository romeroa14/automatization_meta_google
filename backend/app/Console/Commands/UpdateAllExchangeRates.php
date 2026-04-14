<?php

namespace App\Console\Commands;

use App\Services\BcvScraperService;
use App\Services\BinanceScraperService;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateAllExchangeRates extends Command
{
    protected $signature = 'exchange:update-all {currency=USD : Moneda a actualizar} {--source= : Fuente específica (BCV, BINANCE, o ambas)}';
    protected $description = 'Actualiza todas las tasas de cambio (BCV y Binance)';

    public function handle(BcvScraperService $bcvScraper, BinanceScraperService $binanceScraper)
    {
        try {
            $currency = $this->argument('currency') ?? 'USD';
            $source = $this->option('source');

            Log::info('🔄 COMANDO ACTUALIZACIÓN COMPLETA INICIADO', [
                'currency' => $currency,
                'source' => $source,
                'timestamp' => now(),
                'environment' => app()->environment()
            ]);

            $this->info('🔄 Actualizando tasas de cambio...');
            $this->line('');
            
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            // Actualizar BCV
            if (!$source || strtoupper($source) === 'BCV') {
                $this->info('📊 Actualizando tasas BCV...');
                try {
                    if ($currency === 'all') {
                        $bcvRates = $bcvScraper->fetchRates();
                        $results['BCV'] = $bcvRates;
                        $this->info('✅ BCV: ' . count($bcvRates) . ' tasas actualizadas');
                        foreach ($bcvRates as $code => $rate) {
                            $this->line("   {$code}: " . number_format($rate, 2, ',', '.') . " Bs.");
                        }
                    } else {
                        $bcvRate = $bcvScraper->fetchRateForCurrency($currency);
                        $results['BCV'] = [$currency => $bcvRate];
                        $this->info("✅ BCV: {$currency} = " . number_format($bcvRate, 2, ',', '.') . " Bs.");
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $this->error("❌ BCV: Error - " . $e->getMessage());
                    $errorCount++;
                }
                $this->line('');
            }

            // Actualizar Binance
            if (!$source || strtoupper($source) === 'BINANCE') {
                $this->info('📊 Actualizando tasas Binance...');
                try {
                    $binanceRates = $binanceScraper->fetchRates();
                    $results['BINANCE'] = $binanceRates;
                    $this->info('✅ Binance: ' . count($binanceRates) . ' tasas actualizadas');
                    foreach ($binanceRates as $code => $rate) {
                        $this->line("   {$code}: " . number_format($rate, 2, ',', '.') . " Bs.");
                    }
                    $successCount++;
                } catch (Exception $e) {
                    $this->error("❌ Binance: Error - " . $e->getMessage());
                    $errorCount++;
                }
                $this->line('');
            }

            // Mostrar resumen y cálculos
            if (!empty($results)) {
                $this->info('📈 RESUMEN DE ACTUALIZACIÓN:');
                $this->line('');

                // Mostrar comparación si tenemos ambas tasas
                if (isset($results['BCV']['USD']) && isset($results['BINANCE']['USD'])) {
                    $bcvRate = $results['BCV']['USD'];
                    $binanceRate = $results['BINANCE']['USD'];
                    $difference = $binanceRate - $bcvRate;
                    $percentageDiff = ($difference / $bcvRate) * 100;

                    $this->info('💰 COMPARACIÓN DE TASAS USD:');
                    $this->line("   BCV:     " . number_format($bcvRate, 2, ',', '.') . " Bs.");
                    $this->line("   Binance: " . number_format($binanceRate, 2, ',', '.') . " Bs.");
                    $this->line("   Diferencia: " . number_format($difference, 2, ',', '.') . " Bs. (" . number_format($percentageDiff, 2) . "%)");
                    $this->line('');

                    // Mostrar ejemplo de cálculo de precios
                    $this->info('🧮 EJEMPLO DE CÁLCULO DE PRECIOS:');
                    $exampleUsdPrice = 6.00; // Precio original en USD
                    $bcvPrice = \App\Models\ExchangeRate::calculateBcvPriceFromBinance($exampleUsdPrice);
                    
                    if ($bcvPrice) {
                        $this->line("   Precio original: \${$exampleUsdPrice} USD");
                        $this->line("   Precio BCV: " . number_format($bcvPrice, 2, ',', '.') . " Bs.");
                        $this->line("   (Fórmula: \${$exampleUsdPrice} × ({$binanceRate} ÷ {$bcvRate}) = " . number_format($bcvPrice, 2, ',', '.') . " Bs.)");
                    }
                }

                $this->line('');
                $this->info("✅ Completado: {$successCount} fuentes actualizadas, {$errorCount} errores");
            }
            
            Log::info('✅ COMANDO ACTUALIZACIÓN COMPLETA FINALIZADO', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'results' => $results,
                'timestamp' => now(),
                'status' => $errorCount === 0 ? 'success' : 'partial_success'
            ]);
            
            return $errorCount === 0 ? Command::SUCCESS : Command::FAILURE;
        } catch (Exception $e) {
            Log::error('❌ COMANDO ACTUALIZACIÓN COMPLETA FALLÓ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now(),
                'status' => 'failed'
            ]);
            
            $this->error('Error general: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
