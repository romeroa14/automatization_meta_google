<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;

class BinanceScraperService
{
    protected string $url = 'https://exchangemonitor.net/venezuela/dolar-binance';
    protected int $maxRetries = 3;
    protected int $timeout = 30;
    protected int $connectTimeout = 15;

    /**
     * Obtiene las tasas de Binance con reintentos
     */
    public function fetchRates(): array
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                Log::info("Intento {$attempt} de obtener tasas de Binance desde Exchange Monitor");
                return $this->attemptFetchRates();
            } catch (Exception $e) {
                $lastException = $e;
                Log::warning("Intento {$attempt} fallido: " . $e->getMessage());
                
                // Si no es el último intento, esperar antes de reintentar
                if ($attempt < $this->maxRetries) {
                    $sleepSeconds = pow(2, $attempt - 1); // Backoff exponencial
                    Log::info("Esperando {$sleepSeconds} segundos antes del siguiente intento...");
                    sleep($sleepSeconds);
                }
                
                $attempt++;
            }
        }

        // Si llegamos aquí, todos los intentos fallaron
        Log::error("Todos los intentos de obtener tasas de Binance fallaron después de {$this->maxRetries} intentos");
        $this->saveError($lastException->getMessage());
        throw $lastException;
    }

    /**
     * Intento individual de obtener las tasas
     */
    protected function attemptFetchRates(): array
    {
        // Configurar el cliente HTTP con timeouts específicos
        $response = Http::withOptions([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'verify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ]
        ])->get($this->url);
        
        if (!$response->successful()) {
            throw new Exception("Error al obtener la página de Exchange Monitor: " . $response->status());
        }

        $html = $response->body();

        if (empty(trim($html))) {
            throw new Exception("La página de Exchange Monitor devolvió contenido vacío");
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $rates = [];
        
        // Buscar el precio principal del dólar Binance
        // Según la estructura HTML que vimos, buscamos el span con el precio
        $priceNodes = $xpath->query("//span[contains(@class, 'me-1') or contains(@class, 'mx-md-2')]/following-sibling::text()[normalize-space()]");
        
        if ($priceNodes->length > 0) {
            foreach ($priceNodes as $node) {
                $priceText = trim($node->textContent);
                // Buscar un patrón numérico como "232,56" o "233,33"
                if (preg_match('/^(\d{1,3}(?:,\d{2})?)$/', $priceText, $matches)) {
                    $rate = str_replace(',', '.', $matches[1]);
                    if (is_numeric($rate) && $rate > 0) {
                        $rates['USD'] = round(floatval($rate), 2);
                        Log::info("Tasa USD/Binance encontrada: {$rate} (redondeada a 2 decimales)");
                        break;
                    }
                }
            }
        }

        // Método alternativo: buscar por texto que contenga "Bs." seguido de números
        if (empty($rates)) {
            $alternativeNodes = $xpath->query("//text()[contains(., 'Bs.')]");
            foreach ($alternativeNodes as $node) {
                $text = $node->textContent;
                // Buscar patrón "Bs. 232,56" o similar
                if (preg_match('/Bs\.\s*(\d{1,3}(?:,\d{2})?)/', $text, $matches)) {
                    $rate = str_replace(',', '.', $matches[1]);
                    if (is_numeric($rate) && $rate > 0) {
                        $rates['USD'] = round(floatval($rate), 2);
                        Log::info("Tasa USD/Binance encontrada (método alternativo): {$rate} (redondeada a 2 decimales)");
                        break;
                    }
                }
            }
        }

        // Método adicional: buscar en elementos con clases específicas
        if (empty($rates)) {
            $classNodes = $xpath->query("//*[contains(@class, 'price') or contains(@class, 'rate') or contains(@class, 'value')]");
            foreach ($classNodes as $node) {
                $text = trim($node->textContent);
                if (preg_match('/^(\d{1,3}(?:,\d{2})?)$/', $text, $matches)) {
                    $rate = str_replace(',', '.', $matches[1]);
                    if (is_numeric($rate) && $rate > 0 && $rate > 100) { // Validar que sea un precio razonable
                        $rates['USD'] = round(floatval($rate), 2);
                        Log::info("Tasa USD/Binance encontrada (método por clases): {$rate} (redondeada a 2 decimales)");
                        break;
                    }
                }
            }
        }

        if (empty($rates)) {
            throw new Exception("No se encontró la tasa de Binance en la página de Exchange Monitor");
        }

        // Guardar las tasas en la base de datos
        $this->saveRates($rates);

        return $rates;
    }

    /**
     * Obtiene la tasa para USD específicamente
     */
    public function fetchUsdRate(): float
    {
        $rates = $this->fetchRates();
        
        if (!isset($rates['USD'])) {
            throw new Exception("No se encontró la tasa USD en Binance");
        }
        
        return $rates['USD'];
    }

    /**
     * Guarda las tasas en la base de datos
     */
    protected function saveRates(array $rates): void
    {
        try {
            DB::beginTransaction();

            // Primero limpiar registros antiguos si hay cambios
            ExchangeRate::cleanOldRates($rates);

            // Guardar las nuevas tasas
            $now = now();
            foreach ($rates as $currencyCode => $rate) {
                ExchangeRate::create([
                    'currency_code' => $currencyCode,
                    'rate' => $rate,
                    'source' => 'BINANCE',
                    'target_currency' => 'VES',
                    'fetched_at' => $now,
                    'is_valid' => true,
                    'metadata' => [
                        'source_url' => $this->url,
                        'scraped_at' => $now->toISOString(),
                        'method' => 'web_scraping'
                    ]
                ]);

                Log::info("Nueva tasa Binance guardada para {$currencyCode}: {$rate}");
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar tasas de Binance: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra un error en la base de datos
     */
    protected function saveError(string $errorMessage): void
    {
        ExchangeRate::create([
            'currency_code' => 'USD',
            'rate' => 0,
            'source' => 'BINANCE',
            'target_currency' => 'VES',
            'fetched_at' => now(),
            'is_valid' => false,
            'error_message' => $errorMessage,
            'metadata' => [
                'source_url' => $this->url,
                'error_at' => now()->toISOString(),
                'method' => 'web_scraping'
            ]
        ]);
    }

    /**
     * Obtiene información adicional de la página (cambio porcentual, etc.)
     */
    public function fetchAdditionalInfo(): array
    {
        try {
            $response = Http::withOptions([
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ])->get($this->url);
            
            if (!$response->successful()) {
                return [];
            }

            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            $info = [];

            // Buscar cambio porcentual
            $changeNodes = $xpath->query("//text()[contains(., '%')]");
            foreach ($changeNodes as $node) {
                $text = $node->textContent;
                if (preg_match('/([+-]?\d+,\d+)\s*VES\s*\(([+-]?\d+,\d+)%\)/', $text, $matches)) {
                    $info['change_ves'] = str_replace(',', '.', $matches[1]);
                    $info['change_percentage'] = str_replace(',', '.', $matches[2]);
                    break;
                }
            }

            // Buscar timestamp de actualización
            $timeNodes = $xpath->query("//text()[contains(., 'UTC')]");
            foreach ($timeNodes as $node) {
                $text = $node->textContent;
                if (preg_match('/(\d{1,2}:\d{2}\s*[ap]m\s*UTC)/', $text, $matches)) {
                    $info['last_update'] = $matches[1];
                    break;
                }
            }

            return $info;
        } catch (Exception $e) {
            Log::warning("Error al obtener información adicional de Binance: " . $e->getMessage());
            return [];
        }
    }
}
