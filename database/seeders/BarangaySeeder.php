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
            'Poblacion', 'New Carmen', 'La Paz', 'Mangalcal', 
            'Tubod', 'Mabuhay', 'Santo Niño', 'Salvacion',
            'Tuganay', 'Magsaysay', 'Alejal', 'Guadalupe',
            'Ising', 'Minda', 'Tidman', 'Taba'
        ];

        foreach ($barangays as $name) {
            Barangay::create(['name' => $name]);
        }
    }
}