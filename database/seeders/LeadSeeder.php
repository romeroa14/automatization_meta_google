<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        Lead::create([
            'client_name' => 'Cliente Prueba',
            'phone_number' => '123456789',
            'intent' => 'compra',
            'stage' => 'nuevo',
            'confidence_score' => 0.85,
            'lead_level' => 'hot',
            'created_at' => now(),
        ]);
        
        Lead::create([
             'client_name' => 'Interesado Demo',
             'phone_number' => '987654321',
             'intent' => 'consulta',
             'stage' => 'interesado',
             'confidence_score' => 0.45,
             'lead_level' => 'warm',
             'created_at' => now()->subDay(),
         ]);
    }
}
