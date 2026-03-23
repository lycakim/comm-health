<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FamilyRelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $relationships = [
            'Household-Head',
            'Spouse',
            'Child',
            'Sibling',
            'Parent',
            'Grandparent',
            'Grandchild',
            'Son-in-Law',
            'Daughter-in-Law',
            'Parent-in-Law',
            'Sibling-in-Law',
            'Co-Wife',
            'Relative',
            'Boarder',
            'Helper',
        ];

        foreach ($relationships as $name) {
            \App\Models\FamilyRelationship::firstOrCreate(['name' => $name]);
        }
    }
}
