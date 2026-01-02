<?php

namespace Modules\Academic\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Services\SemesterService;
use Modules\Academic\Entities\Semester;
use Modules\Academic\Entities\AcademicYear;

class SemesterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SemesterService $semesterService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semesterService = app(SemesterService::class);
    }

    /** @test */
    public function it_can_create_a_semester()
    {
        $academicYear = AcademicYear::factory()->create();

        $data = [
            'academic_year_id' => $academicYear->id,
            'name' => 'Trimestre 1',
            'type' => 'trimester',
            'number' => 1,
            'start_date' => '2024-09-01',
            'end_date' => '2024-12-15',
            'is_current' => true,
        ];

        $semester = $this->semesterService->createSemester($data);

        $this->assertInstanceOf(Semester::class, $semester);
        $this->assertEquals('Trimestre 1', $semester->name);
        $this->assertEquals('trimester', $semester->type);
        $this->assertTrue($semester->is_current);
        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'academic_year_id' => $academicYear->id,
        ]);
    }

    /** @test */
    public function it_generates_name_automatically_if_not_provided()
    {
        $academicYear = AcademicYear::factory()->create();

        $data = [
            'academic_year_id' => $academicYear->id,
            'type' => 'trimester',
            'number' => 2,
            'start_date' => '2024-12-16',
            'end_date' => '2025-03-15',
        ];

        $semester = $this->semesterService->createSemester($data);

        $this->assertEquals('Trimestre 2', $semester->name);
    }

    /** @test */
    public function it_can_generate_three_trimesters_for_academic_year()
    {
        $academicYear = AcademicYear::factory()->create([
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
        ]);

        $semesters = $this->semesterService->generateSemestersForYear($academicYear->id, 'trimester');

        $this->assertCount(3, $semesters);
        
        foreach ($semesters as $index => $semester) {
            $this->assertInstanceOf(Semester::class, $semester);
            $this->assertEquals('Trimestre ' . ($index + 1), $semester->name);
            $this->assertEquals($index + 1, $semester->number);
            $this->assertEquals('trimester', $semester->type);
        }

        // First trimester should be current
        $this->assertTrue($semesters[0]->is_current);
        $this->assertFalse($semesters[1]->is_current);
        $this->assertFalse($semesters[2]->is_current);
    }

    /** @test */
    public function it_can_generate_two_semesters_for_academic_year()
    {
        $academicYear = AcademicYear::factory()->create([
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
        ]);

        $semesters = $this->semesterService->generateSemestersForYear($academicYear->id, 'semester');

        $this->assertCount(2, $semesters);
        
        foreach ($semesters as $index => $semester) {
            $this->assertInstanceOf(Semester::class, $semester);
            $this->assertEquals('Semestre ' . ($index + 1), $semester->name);
            $this->assertEquals($index + 1, $semester->number);
            $this->assertEquals('semester', $semester->type);
        }
    }

    /** @test */
    public function it_can_set_semester_as_current()
    {
        $academicYear = AcademicYear::factory()->create();
        
        $semester1 = Semester::factory()->create([
            'academic_year_id' => $academicYear->id,
            'is_current' => true,
        ]);
        
        $semester2 = Semester::factory()->create([
            'academic_year_id' => $academicYear->id,
            'is_current' => false,
        ]);

        $this->semesterService->setCurrentSemester($semester2);

        $semester1->refresh();
        $semester2->refresh();

        $this->assertFalse($semester1->is_current);
        $this->assertTrue($semester2->is_current);
    }

    /** @test */
    public function it_can_get_current_semester()
    {
        $semester = Semester::factory()->create(['is_current' => true]);
        Semester::factory()->create(['is_current' => false]);

        $current = $this->semesterService->getCurrentSemester();

        $this->assertInstanceOf(Semester::class, $current);
        $this->assertEquals($semester->id, $current->id);
    }

    /** @test */
    public function it_can_update_semester()
    {
        $semester = Semester::factory()->create([
            'name' => 'Ancien nom',
            'description' => 'Ancienne description',
        ]);

        $updatedSemester = $this->semesterService->updateSemester($semester, [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
        ]);

        $this->assertEquals('Nouveau nom', $updatedSemester->name);
        $this->assertEquals('Nouvelle description', $updatedSemester->description);
    }

    /** @test */
    public function it_can_get_semesters_by_year()
    {
        $year1 = AcademicYear::factory()->create();
        $year2 = AcademicYear::factory()->create();

        Semester::factory()->count(3)->create(['academic_year_id' => $year1->id]);
        Semester::factory()->count(2)->create(['academic_year_id' => $year2->id]);

        $semesters = $this->semesterService->getSemestersByYear($year1->id);

        $this->assertCount(3, $semesters);
        foreach ($semesters as $semester) {
            $this->assertEquals($year1->id, $semester->academic_year_id);
        }
    }

    /** @test */
    public function it_cannot_delete_semester_with_grades()
    {
        $this->markTestSkipped('Requires Grade module to be implemented');
    }
}
