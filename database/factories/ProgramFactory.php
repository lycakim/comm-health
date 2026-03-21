<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addDays(rand(1, 10));

        return [
            'name'               => $this->faker->words(3, true),
            'description'        => $this->faker->sentence(),
            'barangay_id'        => Barangay::factory(),
            'category_id'        => Category::factory(),
            'coordinator'        => User::factory(),
            'created_by'         => null,
            'program_start_date' => $start->toDateString(),
            'program_end_date'   => $start->addDays(5)->toDateString(),
            'program_start_time' => '08:00:00',
            'program_end_time'   => '17:00:00',
        ];
    }

    public function createdBy(int $userId): static
    {
        return $this->state(['created_by' => $userId, 'coordinator' => $userId]);
    }
}
