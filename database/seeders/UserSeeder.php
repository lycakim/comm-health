<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('system2025!'),
                'role' => 'admin',
            ],
            [
                'name' => 'John Doe',
                'email' => 'johndoe@gmail.com',
                'password' => bcrypt('system2025!'),
                'role' => 'bhw',
            ],
            [
                'name' => 'Jennifer Doe',
                'email' => 'jenniferdoe@gmail.com',
                'password' => bcrypt('system2025!'),
                'role' => 'mho',
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'janedoe@gmail.com',
                'password' => bcrypt('system2025!'),
                'role' => 'midwife',
            ],
        ];

        foreach ($users as $user) {
            \App\Models\User::create($user);
        }
    }
}