<?php

namespace App\Console\Commands;

use App\Services\BinanceScraperService;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Support\Facades\Log;

class FetchBinanceRates extends Command
{
    protected $signature = 'binance:fetch-rates {--all : Obtener todas las tasas disponibles}';
    protected $description = 'Obtiene las tasas de cambio actuales de Binance desde Exchange Monitor. Por defecto obtiene USD.';

    public function handle(BinanceScraperService $scraper)
    {
        try {
            $getAllRates = $this->option('all');

            Log::info('📊 COMANDO BINANCE INICIADO', [
                'get_all_rates' => $getAllRates,
                'timestamp' => now(),
                'environment' => app()->environment()
            ]);

            $this->info('Obteniendo tasas de Binance desde Exchange Monitor...');
            
            if ($getAllRates) {
                $rates = $scraper->fetchRates();
                
                Log::info('📈 TASAS BINANCE OBTENIDAS EXITOSAMENTE', [
                    'rates_count' => count($rates),
                    'rates' => $rates,
                    'timestamp' => now()
                ]);
                
                $this->info('Tasas de Binance obtenidas exitosamente:');
                foreach ($rates as $code => $rate) {
                    $this->line(sprintf(
                        "%s: %s Bs.",
                        str_pad($code, 5, ' ', STR_PAD_RIGHT),
                        number_format($rate, 2, ',', '.')
                    ));
                }
            } else {
                $rate = $scraper->fetchUsdRate();
                
                Log::info('📈 TASA USD BINANCE OBTENIDA', [
                    'rate' => $rate,
                    'timestamp' => now()
                ]);
                
                $this->info('Tasa USD/Binance obtenida exitosamente:');
                $this->line(sprintf(
                    "USD: %s Bs.",
                    number_format($rate, 2, ',', '.')
                ));
            }

            // Obtener información adicional
            $additionalInfo = $scraper->fetchAdditionalInfo();
            if (!empty($additionalInfo)) {
                $this->info('Información adicional:');
                if (isset($additionalInfo['change_percentage'])) {
                    $this->line("Cambio: {$additionalInfo['change_percentage']}%");
                }
                if (isset($additionalInfo['last_update'])) {
                    $this->line("Última actualización: {$additionalInfo['last_update']}");
                }
            }
            
            Log::info('✅ COMANDO BINANCE COMPLETADO EXITOSAMENTE', [
                'timestamp' => now(),
                'status' => 'success'
            ]);
            
            return Command::SUCCESS;
        } catch (Exception $e) {
            Log::error('❌ COMANDO BINANCE FALLÓ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now(),
                'status' => 'failed'
            ]);
            
            $this->error('Error al obtener las tasas de Binance: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
