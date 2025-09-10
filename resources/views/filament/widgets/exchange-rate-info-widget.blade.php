<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>📊 Información Detallada de Tasas</span>
                <div class="flex gap-2">
                    {{ $this->refreshAction() }}
                </div>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tasa BCV -->
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">
                        🏛️ Banco Central (BCV)
                    </h3>
                    <span class="text-xs text-blue-600 dark:text-blue-400">
                        {{ $bcv?->fetched_at?->diffForHumans() ?? 'N/A' }}
                    </span>
                </div>
                
                @if($bcv)
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100 mb-2">
                        {{ number_format($bcv->rate, 2, ',', '.') }} Bs.
                    </div>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        Última actualización: {{ $bcv->fetched_at?->format('d/m/Y H:i') }}
                    </div>
                @else
                    <div class="text-gray-500">No disponible</div>
                @endif
            </div>

            <!-- Tasa Binance -->
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">
                        💰 Binance (Exchange Monitor)
                    </h3>
                    <span class="text-xs text-green-600 dark:text-green-400">
                        {{ $binance?->fetched_at?->diffForHumans() ?? 'N/A' }}
                    </span>
                </div>
                
                @if($binance)
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100 mb-2">
                        {{ number_format($binance->rate, 2, ',', '.') }} Bs.
                    </div>
                    <div class="text-sm text-green-700 dark:text-green-300">
                        Última actualización: {{ $binance->fetched_at?->format('d/m/Y H:i') }}
                    </div>
                @else
                    <div class="text-gray-500">No disponible</div>
                @endif
            </div>
        </div>

        @if($comparison)
            <!-- Comparación -->
            <div class="mt-6 bg-gray-50 dark:bg-gray-900/20 p-4 rounded-lg border border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                    📈 Comparación de Tasas
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            +{{ number_format($comparison['difference'], 2, ',', '.') }} Bs.
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Diferencia</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            +{{ number_format($comparison['percentage'], 1) }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Porcentaje</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($comparison['conversion_factor'], 3) }}x
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Factor de Conversión</div>
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($examples))
            <!-- Ejemplos de Cálculo -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                    🧮 Ejemplos de Cálculo de Precios BCV
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Precio USD</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Precio BCV</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Precio Directo VES</th>
                                <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($examples as $example)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 px-3 font-medium">${{ number_format($example['usd'], 2) }}</td>
                                    <td class="py-2 px-3 text-green-600 dark:text-green-400 font-medium">
                                        {{ number_format($example['bcv_price'], 2, ',', '.') }} Bs.
                                    </td>
                                    <td class="py-2 px-3 text-blue-600 dark:text-blue-400">
                                        {{ number_format($example['ves_direct'], 2, ',', '.') }} Bs.
                                    </td>
                                    <td class="py-2 px-3 text-orange-600 dark:text-orange-400">
                                        {{ number_format($example['bcv_price'] - $example['ves_direct'], 2, ',', '.') }} Bs.
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>💡 Explicación:</strong> El "Precio BCV" es el precio que debes cobrar al cliente usando la tasa oficial BCV, 
                        pero que te permite comprar el saldo necesario en Binance para pagar a Meta. 
                        El "Precio Directo VES" sería el precio si usaras directamente la tasa BCV.
                    </div>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
