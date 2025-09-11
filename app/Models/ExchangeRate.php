<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_code',
        'rate',
        'source',
        'target_currency',
        'binance_equivalent',
        'bcv_equivalent',
        'conversion_factor',
        'fetched_at',
        'is_valid',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'binance_equivalent' => 'decimal:8',
        'bcv_equivalent' => 'decimal:8',
        'conversion_factor' => 'decimal:6',
        'fetched_at' => 'datetime',
        'is_valid' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Obtener la tasa más reciente para una moneda específica y fuente
     */
    public static function getLatestRate(string $currencyCode = 'USD', string $source = 'BCV'): ?self
    {
        return static::where('currency_code', strtoupper($currencyCode))
            ->where('source', strtoupper($source))
            ->where('is_valid', true)
            ->latest('fetched_at')
            ->first();
    }

    /**
     * Obtener todas las tasas más recientes por fuente
     */
    public static function getLatestRatesBySource(string $source = 'BCV'): \Illuminate\Support\Collection
    {
        return static::selectRaw('currency_code, rate, MAX(fetched_at) as fetched_at')
            ->where('source', strtoupper($source))
            ->where('is_valid', true)
            ->groupBy('currency_code', 'rate')
            ->get()
            ->keyBy('currency_code');
    }

    /**
     * Obtener todas las tasas más recientes de todas las fuentes
     */
    public static function getAllLatestRates(): \Illuminate\Support\Collection
    {
        return static::selectRaw('currency_code, source, rate, MAX(fetched_at) as fetched_at')
            ->where('is_valid', true)
            ->groupBy('currency_code', 'source', 'rate')
            ->get()
            ->groupBy('source');
    }

    /**
     * Convertir un precio de VES a USD usando una fuente específica
     */
    public static function convertVesToUsd(float $vesAmount, string $currencyCode = 'USD', string $source = 'BCV'): ?float
    {
        $rate = static::getLatestRate($currencyCode, $source);
        if (!$rate) {
            Log::warning("No se encontró tasa para {$currencyCode} desde {$source}");
            return null;
        }
        
        return $vesAmount / $rate->rate;
    }

    /**
     * Convertir un precio de USD a VES usando una fuente específica
     */
    public static function convertUsdToVes(float $usdAmount, string $currencyCode = 'USD', string $source = 'BCV'): ?float
    {
        $rate = static::getLatestRate($currencyCode, $source);
        if (!$rate) {
            Log::warning("No se encontró tasa para {$currencyCode} desde {$source}");
            return null;
        }
        
        return $usdAmount * $rate->rate;
    }

    /**
     * Calcular precio BCV basado en precio Binance
     * Fórmula: Precio_BCV = Precio_USD * (Tasa_Binance / Tasa_BCV)
     */
    public static function calculateBcvPriceFromBinance(float $usdPrice): ?float
    {
        $binanceRate = static::getLatestRate('USD', 'BINANCE');
        $bcvRate = static::getLatestRate('USD', 'BCV');
        
        if (!$binanceRate || !$bcvRate) {
            Log::warning('No se pudieron obtener las tasas de Binance o BCV para calcular precio');
            return null;
        }
        
        // Fórmula: Precio_BCV = Precio_USD * (Tasa_Binance / Tasa_BCV)
        $conversionFactor = $binanceRate->rate / $bcvRate->rate;
        $bcvPrice = $usdPrice * $conversionFactor;
        
        Log::info('Cálculo de precio BCV', [
            'usd_price' => $usdPrice,
            'binance_rate' => $binanceRate->rate,
            'bcv_rate' => $bcvRate->rate,
            'conversion_factor' => $conversionFactor,
            'bcv_price' => $bcvPrice
        ]);
        
        return $bcvPrice;
    }

    /**
     * Calcular ganancia en VES basada en precios BCV y Binance
     */
    public static function calculateProfitInVes(float $bcvPrice, float $binanceCost): ?float
    {
        $bcvRate = static::getLatestRate('USD', 'BCV');
        
        if (!$bcvRate) {
            Log::warning('No se pudo obtener la tasa BCV para calcular ganancia');
            return null;
        }
        
        // Convertir precio BCV a USD, restar costo Binance, convertir resultado a VES
        $usdRevenue = $bcvPrice / $bcvRate->rate;
        $usdProfit = $usdRevenue - $binanceCost;
        $vesProfit = $usdProfit * $bcvRate->rate;
        
        return $vesProfit;
    }

    /**
     * Calcular precio de plan en Binance (USD paralelo)
     * Fórmula: Precio_Binance = Precio_Plan_USD * Tasa_Binance
     */
    public static function calculatePlanPriceInBinance(float $planUsdPrice): ?float
    {
        $binanceRate = static::getLatestRate('USD', 'BINANCE');
        
        if (!$binanceRate) {
            Log::warning('No se pudo obtener la tasa Binance para calcular precio');
            return null;
        }
        
        return $planUsdPrice * $binanceRate->rate;
    }

    /**
     * Calcular precio de plan en BCV
     * Fórmula: Precio_BCV = Precio_Plan_USD * Tasa_BCV
     */
    public static function calculatePlanPriceInBcv(float $planUsdPrice): ?float
    {
        $bcvRate = static::getLatestRate('USD', 'BCV');
        
        if (!$bcvRate) {
            Log::warning('No se pudo obtener la tasa BCV para calcular precio');
            return null;
        }
        
        return $planUsdPrice * $bcvRate->rate;
    }

    /**
     * Calcular equivalencia entre tasas para un precio de plan
     * Retorna: ['binance_price' => float, 'bcv_price' => float, 'conversion_factor' => float]
     */
    public static function calculatePlanPriceEquivalents(float $planUsdPrice): ?array
    {
        $binanceRate = static::getLatestRate('USD', 'BINANCE');
        $bcvRate = static::getLatestRate('USD', 'BCV');
        
        if (!$binanceRate || !$bcvRate) {
            Log::warning('No se pudieron obtener las tasas para calcular equivalencias');
            return null;
        }
        
        $binancePrice = $planUsdPrice * $binanceRate->rate;
        $bcvPrice = $planUsdPrice * $bcvRate->rate;
        $conversionFactor = $binanceRate->rate / $bcvRate->rate;
        
        return [
            'binance_price' => $binancePrice,
            'bcv_price' => $bcvPrice,
            'conversion_factor' => $conversionFactor,
            'binance_rate' => $binanceRate->rate,
            'bcv_rate' => $bcvRate->rate,
        ];
    }

    /**
     * Obtener estadísticas de precios de planes
     */
    public static function getPlanPriceStatistics(): array
    {
        $binanceRate = static::getLatestRate('USD', 'BINANCE');
        $bcvRate = static::getLatestRate('USD', 'BCV');
        
        if (!$binanceRate || !$bcvRate) {
            return [];
        }
        
        $conversionFactor = $binanceRate->rate / $bcvRate->rate;
        $difference = $binanceRate->rate - $bcvRate->rate;
        $percentageDiff = ($difference / $bcvRate->rate) * 100;
        
        return [
            'binance_rate' => $binanceRate->rate,
            'bcv_rate' => $bcvRate->rate,
            'conversion_factor' => $conversionFactor,
            'difference' => $difference,
            'percentage_difference' => $percentageDiff,
            'last_updated' => max($binanceRate->fetched_at, $bcvRate->fetched_at),
        ];
    }

    /**
     * Obtener la tasa formateada
     */
    public function getFormattedRateAttribute(): string
    {
        return number_format($this->rate, 2, ',', '.');
    }

    /**
     * Obtener la fecha formateada
     */
    public function getFormattedFetchedAtAttribute(): string
    {
        return $this->fetched_at->format('d/m/Y H:i');
    }

    /**
     * Obtener el tiempo transcurrido desde la última actualización
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->fetched_at->diffForHumans();
    }

    /**
     * Limpiar tasas antiguas (mantener solo las últimas 24 horas)
     */
    public static function cleanOldRates(array $newRates = []): void
    {
        $cutoffTime = now()->subHours(24);
        
        // Eliminar tasas antiguas
        $deletedCount = static::where('fetched_at', '<', $cutoffTime)->delete();
        
        if ($deletedCount > 0) {
            Log::info("Se eliminaron {$deletedCount} tasas antiguas (anteriores a 24 horas)");
        }
        
        // Si se proporcionan nuevas tasas, eliminar duplicados del mismo momento
        if (!empty($newRates)) {
            foreach ($newRates as $currencyCode => $rate) {
                static::where('currency_code', $currencyCode)
                    ->where('rate', $rate)
                    ->where('fetched_at', '>=', now()->subMinutes(5))
                    ->where('fetched_at', '<=', now()->addMinutes(5))
                    ->delete();
            }
        }
    }

    /**
     * Obtener estadísticas de tasas
     */
    public static function getRateStatistics(): array
    {
        $sources = ['BCV', 'BINANCE'];
        $statistics = [];
        
        foreach ($sources as $source) {
            $latestRates = static::getLatestRatesBySource($source);
            $statistics[$source] = [
                'count' => $latestRates->count(),
                'last_update' => $latestRates->max('fetched_at'),
                'currencies' => $latestRates->pluck('currency_code')->toArray(),
            ];
        }
        
        return $statistics;
    }

    /**
     * Scope para tasas válidas
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope para una fuente específica
     */
    public function scopeSource($query, string $source)
    {
        return $query->where('source', strtoupper($source));
    }

    /**
     * Scope para una moneda específica
     */
    public function scopeCurrency($query, string $currencyCode)
    {
        return $query->where('currency_code', strtoupper($currencyCode));
    }
}
