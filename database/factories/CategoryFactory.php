<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'is_active' => true,
            'is_child' => false,
            'is_maternal' => false,
            'age_min' => null,
            'age_max' => null,
        ];
    }

    public function profiledRegisteredMembers(): static
    {
        return $this->state(['name' => Category::PROFILED_REGISTERED_MEMBERS_NAME]);
    }

    public function withAgeRange(int $min, int $max): static
    {
        return $this->state(['age_min' => $min, 'age_max' => $max]);
    }
}
