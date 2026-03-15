<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header con información general -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        💰 Sistema de Tasas de Cambio
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Monitoreo en tiempo real de las tasas BCV y Binance para cálculos de precios
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Última actualización automática
                    </div>
                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ now()->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
            
            <!-- Información rápida -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center">
                        <div class="text-2xl mr-3">🏛️</div>
                        <div>
                            <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Banco Central (BCV)</div>
                            <div class="text-lg font-bold text-blue-900 dark:text-blue-100">
                                @php
                                    $latestBcv = \App\Models\ExchangeRate::getLatestRate('USD', 'BCV');
                                @endphp
                                @if($latestBcv)
                                    {{ number_format($latestBcv->rate, 2, ',', '.') }} Bs.
                                @else
                                    No disponible
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center">
                        <div class="text-2xl mr-3">💰</div>
                        <div>
                            <div class="text-sm text-green-600 dark:text-green-400 font-medium">Binance</div>
                            <div class="text-lg font-bold text-green-900 dark:text-green-100">
                                @php
                                    $latestBinance = \App\Models\ExchangeRate::getLatestRate('USD', 'BINANCE');
                                @endphp
                                @if($latestBinance)
                                    {{ number_format($latestBinance->rate, 2, ',', '.') }} Bs.
                                @else
                                    No disponible
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center">
                        <div class="text-2xl mr-3">📊</div>
                        <div>
                            <div class="text-sm text-purple-600 dark:text-purple-400 font-medium">Factor de Conversión</div>
                            <div class="text-lg font-bold text-purple-900 dark:text-purple-100">
                                @if($latestBcv && $latestBinance)
                                    {{ number_format($latestBinance->rate / $latestBcv->rate, 3) }}x
                                @else
                                    No disponible
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información adicional -->
        <div class="bg-gray-50 dark:bg-gray-900/20 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                📋 Información del Sistema
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">🔄 Actualización Automática</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• Las tasas se actualizan automáticamente cada 30 segundos</li>
                        <li>• Los gráficos se refrescan cada 60 segundos</li>
                        <li>• Los datos se almacenan con precisión de 2 decimales</li>
                        <li>• Historial disponible para las últimas 24 horas</li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">🧮 Cálculo de Precios</h4>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>• <strong>Fórmula:</strong> Precio BCV = Precio USD × (Tasa Binance ÷ Tasa BCV)</li>
                        <li>• Permite cubrir pagos a Meta usando Binance</li>
                        <li>• Mantiene margen de ganancia en bolívares</li>
                        <li>• Ejemplo: $6 USD → {{ $latestBcv && $latestBinance ? number_format(\App\Models\ExchangeRate::calculateBcvPriceFromBinance(6), 2, ',', '.') . ' Bs.' : 'N/A' }}</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Enlaces a recursos -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                🔗 Acceso Rápido
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('filament.admin.resources.exchange-rates.index') }}" 
                   class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    <div class="text-2xl mr-3">📊</div>
                    <div>
                        <div class="font-medium text-blue-900 dark:text-blue-100">Gestionar Tasas</div>
                        <div class="text-sm text-blue-600 dark:text-blue-400">Ver y editar todas las tasas</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.exchange-rates.create') }}" 
                   class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <div class="text-2xl mr-3">➕</div>
                    <div>
                        <div class="font-medium text-green-900 dark:text-green-100">Agregar Tasa</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Crear nueva tasa manualmente</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>