<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        $levels = ['6ème', '5ème', '4ème', '3ème', '2nde', '1ère', 'Tle'];
        $sections = ['A', 'B', 'C', 'D'];

        return [
            'name' => $this->faker->randomElement($levels) . ' ' . $this->faker->randomElement($sections),
            'level' => $this->faker->randomElement($levels),
            'capacity' => $this->faker->numberBetween(30, 50),
            'is_active' => true,
        ];
    }
}