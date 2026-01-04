<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el primer usuario (el que se crea en DatabaseSeeder)
        $user = \App\Models\User::first();
        
        if (!$user) {
            $this->command->warn('⚠️  No se encontró usuario. Los leads se crearán sin user_id.');
        }

        Lead::create([
            'user_id' => $user?->id,
            'client_name' => 'Cliente Prueba',
            'phone_number' => '123456789',
            'intent' => 'compra',
            'stage' => 'nuevo',
            'confidence_score' => 0.85,
            'lead_level' => 'hot',
            'bot_disabled' => false,
            'created_at' => now(),
        ]);
        
        Lead::create([
             'user_id' => $user?->id,
             'client_name' => 'Interesado Demo',
             'phone_number' => '987654321',
             'intent' => 'consulta',
             'stage' => 'interesado',
             'confidence_score' => 0.45,
             'lead_level' => 'warm',
             'bot_disabled' => false,
             'created_at' => now()->subDay(),
         ]);
         
         $this->command->info('✅ Leads de prueba creados correctamente');
    }
}
