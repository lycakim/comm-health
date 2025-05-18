<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'Jenny Doe',
            'email' => 'jennydoe@gmail.com',
            'password' => bcrypt('system2025!'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'johndoe@gmail.com',
            'password' => bcrypt('system2025!'),
            'role' => 'bhw',
        ]);

        User::factory()->create([
            'name' => 'Jennifer Doe',
            'email' => 'jenniferdoe@gmail.com',
            'password' => bcrypt('system2025!'),
            'role' => 'mho',
        ]);

        $this->call(BarangaySeeder::class);
    }
}