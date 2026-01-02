<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'classroom_id' => Classroom::factory(),
            'year' => date('Y'),
            'status' => 'active',
        ];
    }
}