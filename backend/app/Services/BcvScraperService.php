<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;

class BcvScraperService
{
    protected string $url = 'https://www.bcv.org.ve/';
    protected array $currencyCodes = ['USD', 'EUR', 'CNY', 'TRY', 'RUB'];
    protected int $maxRetries = 3;
    protected int $timeout = 30;
    protected int $connectTimeout = 15;

    /**
     * Obtiene las tasas del BCV con reintentos
     */
    public function fetchRates(): array
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                Log::info("Intento {$attempt} de obtener tasas del BCV");
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
        Log::error("Todos los intentos de obtener tasas fallaron después de {$this->maxRetries} intentos");
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
        ])->get($this->url);
        
        if (!$response->successful()) {
            throw new Exception("Error al obtener la página del BCV: " . $response->status());
        }

        $html = $response->body();

        if (empty(trim($html))) {
            throw new Exception("La página del BCV devolvió contenido vacío");
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $rates = [];
        
        // Buscar las tasas para cada moneda
        foreach ($this->currencyCodes as $currencyCode) {
            // Buscar el div que contiene el texto específico de la moneda
            $currencyNodes = $xpath->query("//span[contains(text(), '{$currencyCode}')]");
            
            if ($currencyNodes->length > 0) {
                $currencyNode = $currencyNodes->item(0);
                if ($currencyNode) {
                    // Buscar el div padre que contiene la tasa
                    $parentDiv = $currencyNode->parentNode;
                    if ($parentDiv) {
                        // Buscar el div con la clase centrado que contiene la tasa
                        $rateDiv = $xpath->query(".//div[contains(@class, 'centrado')]", $parentDiv->parentNode)->item(0);
                        if ($rateDiv) {
                            $rate = str_replace(',', '.', trim($rateDiv->textContent));
                            if (is_numeric($rate)) {
                                $rates[$currencyCode] = round(floatval($rate), 2);
                                Log::info("Tasa {$currencyCode} encontrada: {$rate} (redondeada a 2 decimales)");
                            }
                        }
                    }
                }
            }
        }

        if (empty($rates)) {
            throw new Exception("No se encontraron tasas en la página del BCV");
        }

        // Guardar las tasas en la base de datos
        $this->saveRates($rates);

        return $rates;
    }

    /**
     * Obtiene la tasa para una moneda específica
     */
    public function fetchRateForCurrency(string $currencyCode): float
    {
        $rates = $this->fetchRates();
        
        if (!isset($rates[$currencyCode])) {
            throw new Exception("No se encontró la tasa para {$currencyCode}");
        }
        
        return $rates[$currencyCode];
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
                    'source' => 'BCV',
                    'target_currency' => 'VES',
                    'fetched_at' => $now,
                    'is_valid' => true,
                    'metadata' => [
                        'source_url' => $this->url,
                        'scraped_at' => $now->toISOString(),
                        'method' => 'web_scraping'
                    ]
                ]);

                Log::info("Nueva tasa BCV guardada para {$currencyCode}: {$rate}");
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar tasas BCV: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Registra un error en la base de datos
     */
    protected function saveError(string $errorMessage): void
    {
        foreach ($this->currencyCodes as $currencyCode) {
            ExchangeRate::create([
                'currency_code' => $currencyCode,
                'rate' => 0,
                'source' => 'BCV',
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
    }

    /**
     * Extrae el código de moneda del título
     */
    protected function extractCurrencyCode(string $title): ?string
    {
        $title = strtoupper($title);
        foreach ($this->currencyCodes as $code) {
            if (strpos($title, $code) !== false) {
                return $code;
            }
        }
        return null;
    }
}
