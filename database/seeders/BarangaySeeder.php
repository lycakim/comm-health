<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangays = [
            'Alejal',
            'Guadalupe', 
            'Ising',
            'La Paz',
            'Mabuhay',
            'Magsaysay',
            'Mangalcal',
            'Minda',
            'New Carmen',
            'Poblacion',
            'Salvacion',
            'Santo Niño',
            'Taba',
            'Tidman',
            'Tubod',
            'Tuganay'
        ];

        foreach ($barangays as $name) {
            Barangay::create(['name' => $name]);
        }
    }
}