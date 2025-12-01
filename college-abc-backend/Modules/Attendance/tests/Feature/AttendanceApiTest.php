<?php

namespace Modules\Attendance\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Student\Entities\Student;
use Modules\Attendance\Entities\Session;
use Modules\Attendance\Entities\Attendance;
use Modules\Core\Entities\User;
use Laravel\Sanctum\Sanctum;

class AttendanceApiTest extends TestCase
{
    // use RefreshDatabase;

    protected User $user;
    protected Student $student;
    protected Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur de test
        $this->user = User::factory()->create();
        $this->user->assignRole('teacher');

        // Créer un étudiant de test
        $this->student = Student::factory()->create();

        // Créer une session de test
        $this->session = Session::factory()->create();

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_mark_student_attendance()
    {
        $data = [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'present',
            'teacher_notes' => 'Présent et attentif',
        ];

        $response = $this->postJson('/api/v1/attendances/mark', $data);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'student',
                        'session',
                        'status',
                        'recorded_at',
                    ]
                ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'present',
        ]);
    }

    /** @test */
    public function it_can_mark_absent_with_reason()
    {
        $data = [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'absent',
            'absence_reason' => 'illness',
            'absence_notes' => 'Malade selon les parents',
        ];

        $response = $this->postJson('/api/v1/attendances/mark', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'absent',
            'absence_reason' => 'illness',
        ]);
    }

    /** @test */
    public function it_can_bulk_mark_attendance()
    {
        $student2 = Student::factory()->create();

        $data = [
            'session_id' => $this->session->id,
            'attendances' => [
                [
                    'student_id' => $this->student->id,
                    'status' => 'present',
                ],
                [
                    'student_id' => $student2->id,
                    'status' => 'absent',
                    'absence_reason' => 'personal_reasons',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/attendances/bulk-mark', $data);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'attendances',
                        'count',
                    ]
                ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'present',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student2->id,
            'session_id' => $this->session->id,
            'status' => 'absent',
        ]);
    }

    /** @test */
    public function it_can_get_student_attendance_history()
    {
        // Créer quelques présences
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'present',
        ]);

        $response = $this->getJson("/api/v1/attendances/by-student/{$this->student->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'attendances',
                        'stats',
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_session_attendance()
    {
        Attendance::factory()->create([
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'present',
        ]);

        $response = $this->getJson("/api/v1/attendances/by-session/{$this->session->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                ]);
    }

    /** @test */
    public function it_validates_required_fields_for_marking_attendance()
    {
        $data = [
            'student_id' => $this->student->id,
            // session_id manquant
            'status' => 'present',
        ];

        $response = $this->postJson('/api/v1/attendances/mark', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['session_id']);
    }

    /** @test */
    public function it_validates_attendance_status()
    {
        $data = [
            'student_id' => $this->student->id,
            'session_id' => $this->session->id,
            'status' => 'invalid_status',
        ];

        $response = $this->postJson('/api/v1/attendances/mark', $data);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
    }
}
