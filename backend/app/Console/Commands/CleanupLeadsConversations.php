<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class CleanupLeadsConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:leads-conversations {--phone=584242536795} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar leads y conversaciones, manteniendo solo un lead de un número específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumber = $this->option('phone');
        $force = $this->option('force');

        if (!$force) {
            if (!$this->confirm("¿Estás seguro de que quieres eliminar todas las conversaciones y dejar solo un lead de {$phoneNumber}?")) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $this->info('🧹 Iniciando limpieza...');

        // 1. Contar conversaciones antes
        $conversationsCount = Conversation::count();
        $this->info("📊 Conversaciones encontradas: {$conversationsCount}");

        // 2. Eliminar todas las conversaciones
        Conversation::truncate();
        $this->info('✅ Todas las conversaciones eliminadas');

        // 3. Encontrar leads del número especificado
        $leads = Lead::where('phone_number', $phoneNumber)->get();
        $this->info("📊 Leads encontrados para {$phoneNumber}: {$leads->count()}");

        if ($leads->count() > 0) {
            // Mantener el lead más reciente (o el primero si no hay created_at)
            $leadToKeep = $leads->sortByDesc('created_at')->first();
            
            $this->info("✅ Manteniendo lead ID: {$leadToKeep->id} (Creado: {$leadToKeep->created_at})");

            // Eliminar los demás leads del mismo número
            $deleted = Lead::where('phone_number', $phoneNumber)
                ->where('id', '!=', $leadToKeep->id)
                ->delete();
            
            $this->info("🗑️  Eliminados {$deleted} leads duplicados");
        } else {
            $this->warn("⚠️  No se encontraron leads para el número {$phoneNumber}");
        }

        // 4. Verificar resultados
        $remainingLeads = Lead::where('phone_number', $phoneNumber)->count();
        $remainingConversations = Conversation::count();

        $this->info('');
        $this->info('📊 Resumen:');
        $this->info("   - Leads restantes para {$phoneNumber}: {$remainingLeads}");
        $this->info("   - Conversaciones restantes: {$remainingConversations}");
        $this->info('');
        $this->info('✅ Limpieza completada!');

        return 0;
    }
}

