<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario de prueba sin usar factory
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('12345'),
        ]);
        
        $this->command->info('✅ Usuario de prueba creado: test@example.com / 12345');
    }
}
