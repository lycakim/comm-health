<?php

namespace Database\Seeders;

use App\Models\Occupation;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OccupationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $occupations = [
            ['name' => 'Government Employee'],
            ['name' => 'Private Employee'],
            ['name' => 'Self-employed'],
            ['name' => 'Retired'],
            ['name' => 'Unemployed'],
            ['name' => 'Farmer'],
            ['name' => 'Fisherman'],
            ['name' => 'Laborer (Construction)'],
            ['name' => 'Carpenter'],
            ['name' => 'Banana Peeler'],
            ['name' => 'Vendor'],
            ['name' => 'Driver'],
            ['name' => 'Housekeeper'],
            ['name' => 'None'],
        ];

        foreach ($occupations as $work) {
            Occupation::create($work);
        }
    }
}