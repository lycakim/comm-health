<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name'       => $this->faker->firstName(),
            'last_name'        => $this->faker->lastName(),
            'middle_name'      => $this->faker->lastName(),
            'suffix'           => null,
            'sex'              => $this->faker->randomElement(['Male', 'Female']),
            'civil_status'     => 'Single',
            'birth_date'       => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'barangay_id'      => Barangay::factory(),
            'category_id'      => Category::factory(),
            'contact_number'   => '09' . $this->faker->numerify('#########'),
            'blood_pressure'   => '120/80',
            'household_head_id' => null,
        ];
    }

    public function inBarangay(int $barangayId): static
    {
        return $this->state(['barangay_id' => $barangayId]);
    }

    public function inCategory(int $categoryId): static
    {
        return $this->state(['category_id' => $categoryId]);
    }

    public function withHouseholdHead(int $headId): static
    {
        return $this->state(['household_head_id' => $headId]);
    }
}
