<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\AdvertisingPlansSeeder;
use Database\Seeders\FacebookAccountSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Alfredo Romero',
            'email' => 'alfredoromerox15@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            AdvertisingPlansSeeder::class,
            FacebookAccountSeeder::class,
            UserSeeder::class,
            LeadSeeder::class,
        ]);
    }
}
