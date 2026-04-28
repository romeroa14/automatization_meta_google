<!-- Client Portal Dashboard -->
<div class="fi-page-content">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Conversations -->
        <div class="fi-card bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Total Conversaciones</div>
            <div class="text-3xl font-bold text-primary-600">{{ $stats['conversations_total'] ?? 0 }}</div>
        </div>
        
        <!-- Active Conversations -->
        <div class="fi-card bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Conversaciones Activas</div>
            <div class="text-3xl font-bold text-green-600">{{ $stats['conversations_active'] ?? 0 }}</div>
        </div>
        
        <!-- Messages Today -->
        <div class="fi-card bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Mensajes Hoy</div>
            <div class="text-3xl font-bold text-blue-600">{{ $stats['messages_today'] ?? 0 }}</div>
        </div>
        
        <!-- New Leads -->
        <div class="fi-card bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="text-sm text-gray-500">Leads Nuevos</div>
            <div class="text-3xl font-bold text-yellow-600">{{ $stats['leads_new'] ?? 0 }}</div>
        </div>
    </div>
    
    <!-- Recent Conversations -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">Conversaciones Recientes</h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Último Mensaje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentConversations as $conv)
                    <tr>
                        <td class="px-4 py-2">{{ $conv->customer_id }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full {{ $conv->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $conv->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $conv->last_message_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">No hay conversaciones</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recent Leads -->
    <div>
        <h3 class="text-lg font-semibold mb-3">Leads Recientes</h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Intención</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Etapa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentLeads as $lead)
                    <tr>
                        <td class="px-4 py-2">{{ $lead->client_name }}</td>
                        <td class="px-4 py-2">{{ $lead->phone_number }}</td>
                        <td class="px-4 py-2">{{ $lead->intent }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 text-xs rounded-full {{ 
                                $lead->stage === 'nuevo' ? 'bg-gray-100' : 
                                ($lead->stage === 'contactado' ? 'bg-yellow-100' : 
                                ($lead->stage === 'interesado' ? 'bg-blue-100' : 'bg-green-100')) }}">
                                {{ $lead->stage }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No hay leads</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>