<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BarangayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->unique()->word()),
            'is_active' => true,
        ];
    }
}
