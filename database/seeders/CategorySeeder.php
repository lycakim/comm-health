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
            ['name' => 'Child', 'is_child' => true],
            ['name' => 'Senior Citizen', 'is_child' => false],
            ['name' => 'Maternal', 'is_child' => false],
            ['name' => 'Chronic Conditions', 'is_child' => false],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}