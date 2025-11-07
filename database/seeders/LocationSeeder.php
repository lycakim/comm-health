<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Region XI (Davao Region)
        $region = [
            'Davao del Sur' => [
                'Davao City' => ['type' => 'city', 'barangays' => ['Buhangin', 'Matina', 'Talomo']],
                'Digos City' => ['type' => 'city', 'barangays' => ['Aplaya', 'Tres de Mayo']],
                'Bansalan' => ['barangays' => ['Poblacion', 'New Clarin']],
                'Santa Cruz' => ['barangays' => ['Zone I', 'Zone II']],
            ],

            'Davao del Norte' => [
                'Tagum City' => ['type' => 'city', 'barangays' => ['Magugpo East', 'Visayan Village']],
                'Panabo City' => ['type' => 'city', 'barangays' => ['Gredu', 'San Francisco']],
                'Carmen' => ['barangays' => ['Mabuhay', 'Tibulao']],
            ],

            'Davao de Oro' => [
                'Nabunturan' => ['barangays' => ['Magsaysay', 'San Roque']],
                'Maco' => ['barangays' => ['Anibongan', 'Elizalde']],
            ],

            'Davao Oriental' => [
                'Mati City' => ['type' => 'city', 'barangays' => ['Central', 'Sainz']],
                'Lupon' => ['barangays' => ['Poblacion', 'Bagumbayan']],
            ],

            'Davao Occidental' => [
                'Malita' => ['barangays' => ['Poblacion', 'Felix Fojas']],
            ],
        ];

        foreach ($region as $provinceName => $municipalities) {
            $province = Location::create([
                'name' => $provinceName,
                'type' => 'province',
            ]);

            foreach ($municipalities as $cityOrMunicipalityName => $data) {
                // Create city or municipality
                $cityOrMunicipality = Location::create([
                    'name' => $cityOrMunicipalityName,
                    'type' => $data['type'] ?? 'municipality',
                    'parent_id' => $province->id,
                ]);

                // Barangays
                if (isset($data['barangays'])) {
                    foreach ($data['barangays'] as $barangayName) {
                        $barangay = Location::create([
                            'name' => $barangayName,
                            'type' => 'barangay',
                            'parent_id' => $cityOrMunicipality->id,
                        ]);

                        // Example Puroks per barangay
                        foreach (['Purok 1', 'Purok 2', 'Purok 3'] as $purokName) {
                            Location::create([
                                'name' => $purokName,
                                'type' => 'purok',
                                'parent_id' => $barangay->id,
                            ]);
                        }
                    }
                }
            }
        }
    }
}