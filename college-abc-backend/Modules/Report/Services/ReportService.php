<?php

namespace Modules\Report\Services;

use Modules\Student\Entities\Student;
use Modules\Academic\Entities\Semester;
use Modules\Gradebook\Services\GradebookService;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    public function __construct(protected GradebookService $gradebookService) {}

    public function generateReportCard(int $studentId, int $semesterId)
    {
        $student = Student::with(['currentEnrollment.classRoom.level', 'guardians'])->findOrFail($studentId);
        $semester = Semester::with('academicYear')->findOrFail($semesterId);
        $reportData = $this->gradebookService->generateReportCard($studentId, $semesterId);

        $data = [
            'student' => $student,
            'semester' => $semester,
            'report' => $reportData,
            'college_name' => config('app.college_name', 'Collège Wend-Manegda'),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('report::bulletin', $data);
        return $pdf->setPaper('a4')->setOption('margin-top', 10);
    }

    public function generateCertificate(int $studentId, string $type = 'scolarite')
    {
        $student = Student::with(['currentEnrollment.classRoom', 'guardians'])->findOrFail($studentId);

        $data = [
            'student' => $student,
            'type' => $type,
            'college_name' => config('app.college_name'),
            'issued_date' => now(),
            'certificate_number' => $this->generateCertificateNumber($studentId, $type),
        ];

        $pdf = Pdf::loadView("report::certificate_{$type}", $data);
        return $pdf->setPaper('a4');
    }

    public function generateTranscript(int $studentId, ?int $academicYearId = null)
    {
        $student = Student::with('enrollments.classRoom')->findOrFail($studentId);
        $semesters = Semester::when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))->get();

        $transcriptData = [];
        foreach ($semesters as $semester) {
            $transcriptData[] = $this->gradebookService->generateReportCard($studentId, $semester->id);
        }

        $data = ['student' => $student, 'semesters' => $transcriptData, 'college_name' => config('app.college_name')];
        
        $pdf = Pdf::loadView('report::transcript', $data);
        return $pdf->setPaper('a4');
    }

    public function exportClassGrades(int $classRoomId, int $semesterId, string $format = 'pdf')
    {
        // Implementation for Excel/PDF export
        $students = \Modules\Academic\Entities\ClassRoom::findOrFail($classRoomId)->students;
        $data = [];

        foreach ($students as $student) {
            $data[] = array_merge(
                ['student' => $student->full_name, 'matricule' => $student->matricule],
                $this->gradebookService->generateReportCard($student->id, $semesterId)
            );
        }

        if ($format === 'excel') {
            // Excel export logic
            return $data; // Placeholder
        }

        // PDF export
        $pdf = Pdf::loadView('report::class_grades', ['students' => $data, 'semester_id' => $semesterId]);
        return $pdf->setPaper('a4', 'landscape');
    }

    protected function generateCertificateNumber(int $studentId, string $type): string
    {
        return strtoupper(substr($type, 0, 3)) . date('Y') . str_pad($studentId, 5, '0', STR_PAD_LEFT);
    }
}
