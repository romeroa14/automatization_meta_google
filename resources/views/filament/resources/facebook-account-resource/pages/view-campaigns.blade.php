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
                                    Alcance
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
                                    Interacciones
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tasa Interacción
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Videos Completados
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CPM
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CPC
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->ads as $ad)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($ad['creative'] && ($ad['creative']['local_image_path'] || $ad['creative']['thumbnail_url']))
                                                <div class="flex-shrink-0 h-12 w-12 mr-3 relative">
                                                    @php
                                                        $imageUrl = $ad['creative']['local_image_path'] 
                                                            ? asset($ad['creative']['local_image_path']) 
                                                            : $ad['creative']['thumbnail_url'];
                                                    @endphp
                                                    <img class="h-12 w-12 rounded-lg object-cover border border-gray-200" 
                                                         src="{{ $imageUrl }}" 
                                                         alt="Anuncio {{ $ad['ad_name'] }}"
                                                         loading="lazy"
                                                         onerror="this.parentElement.innerHTML='<div class=\'h-12 w-12 bg-gray-200 rounded-lg flex items-center justify-center\'><svg class=\'h-6 w-6 text-gray-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2v12a2 2 0 002 2z\'></path></svg></div>';">
                                                    @if($ad['creative']['local_image_path'])
                                                        <div class="absolute -top-1 -right-1 bg-green-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center" title="Imagen local">
                                                            ✓
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="flex-shrink-0 h-12 w-12 mr-3 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $ad['ad_name'] }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: {{ $ad['ad_id'] }}
                                                </div>
                                                @if($ad['creative'] && $ad['creative']['title'])
                                                    <div class="text-xs text-gray-400 mt-1">
                                                        {{ Str::limit($ad['creative']['title'], 50) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($ad['impressions']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($ad['reach']) }}
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
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ number_format($ad['total_interactions']) }}
                                        </div>
                                        @if(!empty($ad['interactions']))
                                            <button type="button" 
                                                    class="text-xs text-blue-600 hover:text-blue-800 mt-1"
                                                    onclick="toggleInteractions('{{ $ad['ad_id'] }}')">
                                                Ver detalles
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ad['interaction_rate'] > 2 ? 'bg-green-100 text-green-800' : ($ad['interaction_rate'] > 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ number_format($ad['interaction_rate'], 2) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        @if($ad['video_views']['p100'] > 0)
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ number_format($ad['video_views']['p100']) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ number_format($ad['video_completion_rate'], 1) }}% tasa
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ${{ number_format($ad['cpm'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ${{ number_format($ad['cpc'], 2) }}
                                    </td>
                                    
                                </tr>
                                <!-- Detalles de interacciones expandibles -->
                                @if(!empty($ad['interactions']))
                                    <tr id="interactions-{{ $ad['ad_id'] }}" class="hidden bg-gray-50">
                                        <td colspan="10" class="px-6 py-4">
                                            <div class="bg-white rounded-lg p-4 border">
                                                <h4 class="text-sm font-medium text-gray-900 mb-3">Detalles de Interacciones</h4>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                                    @foreach($ad['interactions'] as $interaction)
                                                        <div class="bg-gray-50 p-3 rounded">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{ $interaction['label'] }}
                                                            </div>
                                                            <div class="text-lg font-bold text-blue-600">
                                                                {{ number_format($interaction['value']) }}
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
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
                    $totalInteractions = collect($this->ads)->sum('total_interactions');
                    $totalVideoViews = collect($this->ads)->sum('video_views.p100');
                    $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
                    $avgCpm = $totalImpressions > 0 ? ($totalSpend / $totalImpressions) * 1000 : 0;
                    $avgCpc = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
                    $avgInteractionRate = $totalImpressions > 0 ? ($totalInteractions / $totalImpressions) * 100 : 0;
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

                    <div class="bg-indigo-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-indigo-600">{{ number_format($totalInteractions) }}</div>
                        <div class="text-sm text-indigo-600">Interacciones</div>
                    </div>

                    <div class="bg-pink-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-pink-600">{{ number_format($avgInteractionRate, 2) }}%</div>
                        <div class="text-sm text-pink-600">Tasa Interacción</div>
                    </div>

                    <div class="bg-orange-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600">{{ number_format($totalVideoViews) }}</div>
                        <div class="text-sm text-orange-600">Videos Completados</div>
                    </div>

                    <div class="bg-teal-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-teal-600">${{ number_format($avgCpm, 2) }}</div>
                        <div class="text-sm text-teal-600">CPM Promedio</div>
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

    <script>
        function toggleInteractions(adId) {
            const row = document.getElementById('interactions-' + adId);
            if (row) {
                row.classList.toggle('hidden');
            }
        }
    </script>
</x-filament-panels::page> 