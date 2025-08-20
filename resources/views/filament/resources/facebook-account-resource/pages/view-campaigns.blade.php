<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Formulario de selección -->
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
        </div>

        <!-- Loading indicator -->
        @if($this->isLoading)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                    <span class="ml-2 text-gray-600">Cargando datos...</span>
                </div>
            </div>
        @endif

        <!-- Tabla de anuncios -->
        @if(!empty($this->ads))
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Anuncios de la Campaña
                    </h3>
                    <p class="text-sm text-gray-600">
                        {{ count($this->ads) }} anuncios encontrados
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Anuncio
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Impresiones
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Clicks
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Gasto
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CTR
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CPM
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CPC
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alcance
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->ads as $ad)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $ad['ad_name'] }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: {{ $ad['ad_id'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($ad['impressions']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($ad['clicks']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ${{ number_format($ad['spend'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($ad['ctr'], 2) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ${{ number_format($ad['cpm'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ${{ number_format($ad['cpc'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($ad['reach']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen de métricas -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen de Métricas</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @php
                        $totalImpressions = collect($this->ads)->sum('impressions');
                        $totalClicks = collect($this->ads)->sum('clicks');
                        $totalSpend = collect($this->ads)->sum('spend');
                        $totalReach = collect($this->ads)->sum('reach');
                        $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
                        $avgCpm = $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0;
                        $avgCpc = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
                    @endphp

                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ number_format($totalImpressions) }}</div>
                        <div class="text-sm text-blue-600">Impresiones</div>
                    </div>

                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ number_format($totalClicks) }}</div>
                        <div class="text-sm text-green-600">Clicks</div>
                    </div>

                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">${{ number_format($totalSpend, 2) }}</div>
                        <div class="text-sm text-yellow-600">Gasto Total</div>
                    </div>

                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600">{{ number_format($avgCtr, 2) }}%</div>
                        <div class="text-sm text-purple-600">CTR Promedio</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Mensaje cuando no hay anuncios -->
        @if($this->selectedCampaign && empty($this->ads) && !$this->isLoading)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-center">
                    <div class="text-gray-400 text-6xl mb-4">📊</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron anuncios</h3>
                    <p class="text-gray-600">
                        No hay anuncios con datos para la campaña seleccionada en el período especificado.
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page> 