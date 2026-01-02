<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'matricule' => 'STD-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-6 years')->format('Y-m-d'),
            'place_of_birth' => $this->faker->city(),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'address' => $this->faker->address(),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'status' => 'active',
        ];
    }
}