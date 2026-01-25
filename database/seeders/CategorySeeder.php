<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Baby', 'age_min' => 0, 'age_max' => 2, 'is_maternal' => false],
            ['name' => 'Children and Adolescents', 'age_min' => 3, 'age_max' => 20, 'is_maternal' => false],
            ['name' => 'Senior Citizen', 'age_min' => 60, 'age_max' => null, 'is_maternal' => false],
            ['name' => 'Maternal', 'age_min' => null, 'age_max' => null, 'is_maternal' => true],
            ['name' => 'Chronic Conditions', 'age_min' => null, 'age_max' => null, 'is_maternal' => false],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}