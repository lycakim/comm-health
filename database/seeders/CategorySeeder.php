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
            ['name' => 'Child', 'is_child' => true, 'is_maternal' => false],
            ['name' => 'Senior Citizen', 'is_child' => false, 'is_maternal' => false],
            ['name' => 'Maternal', 'is_child' => false, 'is_maternal' => true],
            ['name' => 'Chronic Conditions', 'is_child' => false, 'is_maternal' => false],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}