<?php

namespace Modules\ParentPortal\Services;

use Modules\Student\Entities\Student;
use Modules\Student\Entities\Guardian;
use Modules\Attendance\Entities\Attendance;
use Modules\Gradebook\Services\GradebookService;
use Modules\Finance\Entities\Invoice;
use Modules\Finance\Entities\Payment;

class ParentPortalService
{
    public function __construct(protected GradebookService $gradebookService) {}

    public function getChildrenByGuardian(int $guardianId)
    {
        return Guardian::where('id', $guardianId)->get()
                      ->map(fn($g) => $g->student)
                      ->load(['currentEnrollment.classRoom']);
    }

    public function getStudentDashboard(int $studentId): array
    {
        $student = Student::with(['currentEnrollment.classRoom', 'guardians'])->findOrFail($studentId);
        
        return [
            'student' => $student,
            'attendance_rate' => $this->getAttendanceRate($studentId),
            'latest_grades' => $this->getLatestGrades($studentId, 5),
            'current_average' => $this->gradebookService->getStudentAverage($studentId),
            'pending_invoices' => $this->getPendingInvoices($studentId),
            'recent_absences' => $this->getRecentAbsences($studentId, 7),
        ];
    }

    public function getAttendanceRate(int $studentId, int $days = 30): float
    {
        $total = Attendance::byStudent($studentId)
                          ->where('date', '>=', now()->subDays($days))
                          ->count();
        
        if ($total === 0) return 100.0;

        $present = Attendance::byStudent($studentId)
                            ->where('date', '>=', now()->subDays($days))
                            ->whereIn('status', ['present', 'excused'])
                            ->count();

        return round(($present / $total) * 100, 2);
    }

    public function getLatestGrades(int $studentId, int $limit = 10)
    {
        return \Modules\Gradebook\Entities\Grade::byStudent($studentId)
                                               ->with(['evaluation.subject'])
                                               ->latest('created_at')
                                               ->limit($limit)
                                               ->get();
    }

    public function getPendingInvoices(int $studentId)
    {
        return Invoice::where('student_id', $studentId)
                     ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
                     ->with('feeTypes')
                     ->get();
    }

    public function getRecentAbsences(int $studentId, int $days = 7)
    {
        return Attendance::byStudent($studentId)
                        ->whereIn('status', ['absent', 'late'])
                        ->where('date', '>=', now()->subDays($days))
                        ->with('justification')
                        ->get();
    }

    public function downloadReportCard(int $studentId, int $semesterId)
    {
        return app(\Modules\Report\Services\ReportService::class)
              ->generateReportCard($studentId, $semesterId);
    }

    public function getPaymentHistory(int $studentId)
    {
        return Payment::where('student_id', $studentId)
                     ->with('feeType')
                     ->orderBy('payment_date', 'desc')
                     ->get();
    }
}
