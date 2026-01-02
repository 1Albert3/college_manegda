<?php

namespace Modules\Student\Services;

use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StudentCardService
{
    public function generateCard(int $studentId)
    {
        $student = Student::with(['currentEnrollment.classRoom.level', 'guardians'])
                         ->findOrFail($studentId);
        
        $academicYear = AcademicYear::where('is_current', true)->first();

        // Générer QR Code pour vérification
        $qrData = "STUDENT:{$student->matricule}|YEAR:{$academicYear->name}";
        $qrCode = QrCode::size(80)->generate($qrData);

        $data = [
            'student' => $student,
            'academic_year' => $academicYear,
            'qr_code' => base64_encode($qrCode),
            'college_name' => config('app.college_name', 'Collège Wend-Manegda'),
            'college_address' => config('app.college_address', 'Ouagadougou'),
            'issued_date' => now(),
        ];

        $pdf = Pdf::loadView('student::card', $data);
        return $pdf->setPaper([0, 0, 224, 354], 'portrait'); // Format carte 8.5×5.5cm
    }

    public function generateCardSheet(array $studentIds)
    {
        $students = Student::with(['currentEnrollment.classRoom.level', 'guardians'])
                          ->whereIn('id', $studentIds)
                          ->get();
        
        $academicYear = AcademicYear::where('is_current', true)->first();
        $cards = [];

        foreach ($students as $student) {
            $qrData = "STUDENT:{$student->matricule}|YEAR:{$academicYear->name}";
            $qrCode = QrCode::size(60)->generate($qrData);
            
            $cards[] = [
                'student' => $student,
                'qr_code' => base64_encode($qrCode),
            ];
        }

        $data = [
            'cards' => $cards,
            'academic_year' => $academicYear,
            'college_name' => config('app.college_name'),
        ];

        // Format A4 avec 10 cartes (2 colonnes × 5 lignes)
        $pdf = Pdf::loadView('student::card_sheet', $data);
        return $pdf->setPaper('a4');
    }

    public function generateClassCards(int $classRoomId)
    {
        $students = \Modules\Academic\Entities\ClassRoom::findOrFail($classRoomId)
                   ->students()
                   ->pluck('id')
                   ->toArray();

        return $this->generateCardSheet($students);
    }
}
