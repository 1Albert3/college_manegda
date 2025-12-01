<?php

namespace Modules\Attendance\Services;

use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\Session;
use Modules\Attendance\Entities\Justification;
use Modules\Student\Entities\Student;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    public function __construct(
        private AttendanceRepository $repository,
        private $smsService = null
    ) {}

    public function markAttendance(int $studentId, int $sessionId, array $data): Attendance
    {
        return DB::transaction(function () use ($studentId, $sessionId, $data) {
            $attendance = $this->repository->markAttendance(
                $studentId,
                $sessionId,
                $data,
                Auth::id()
            );

            // Envoyer SMS si absent
            if (($data['status'] ?? 'present') === 'absent') {
                $this->notifyParentAbsence($studentId, $sessionId);
            }

            return $attendance;
        });
    }

    public function bulkMarkAttendance(int $sessionId, array $attendances): Collection
    {
        return DB::transaction(function () use ($sessionId, $attendances) {
            $results = collect();

            foreach ($attendances as $attendance) {
                $result = $this->markAttendance(
                    $attendance['student_id'],
                    $sessionId,
                    $attendance
                );
                $results->push($result);
            }

            // Mettre à jour les statistiques de la session
            $session = Session::find($sessionId);
            if ($session) {
                $session->updateAttendanceStats();
            }

            return $results;
        });
    }

    public function getStudentAttendance(int $studentId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->repository->getByStudent($studentId, $startDate, $endDate);
    }

    public function getSessionAttendance(int $sessionId): Collection
    {
        return $this->repository->getBySession($sessionId);
    }

    public function getClassAttendance(int $classId, string $date): Collection
    {
        return $this->repository->getByClassAndDate($classId, $date);
    }

    public function calculateAttendanceRate(int $studentId, ?string $startDate = null, ?string $endDate = null): float
    {
        return $this->repository->getAttendanceRate($studentId, $startDate, $endDate);
    }

    public function getMonthlyReport(int $classId, int $month, int $year): array
    {
        $attendances = $this->repository->getMonthlyReport($classId, $month, $year);

        $report = [];
        foreach ($attendances as $studentId => $studentAttendances) {
            $student = $studentAttendances->first()->student;

            $stats = [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'matricule' => $student->matricule,
                'present' => $studentAttendances->where('status', 'present')->count(),
                'absent' => $studentAttendances->where('status', 'absent')->count(),
                'late' => $studentAttendances->where('status', 'late')->count(),
                'excused' => $studentAttendances->where('status', 'excused')->count(),
                'justified' => $studentAttendances->where('justified', true)->count(),
            ];

            $stats['total_sessions'] = $stats['present'] + $stats['absent'] + $stats['late'] + $stats['excused'];
            $stats['attendance_rate'] = $stats['total_sessions'] > 0
                ? round(($stats['present'] / $stats['total_sessions']) * 100, 2)
                : 0;

            $report[] = $stats;
        }

        return $report;
    }

    public function getAttendanceStats(int $studentId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->repository->getAttendanceStats($studentId, $startDate, $endDate);
    }

    public function getClassAttendanceStats(int $classId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->repository->getClassAttendanceStats($classId, $startDate, $endDate);
    }

    public function getUnjustifiedAbsences(int $studentId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->repository->getUnjustifiedAbsences($studentId, $startDate, $endDate);
    }

    public function getLateArrivals(int $studentId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        return $this->repository->getLateArrivals($studentId, $startDate, $endDate);
    }

    public function submitJustification(int $attendanceId, array $data): Justification
    {
        return DB::transaction(function () use ($attendanceId, $data) {
            $attendance = $this->repository->findOrFail($attendanceId);

            if (!$attendance->canBeJustified()) {
                throw new \Exception('Cette absence ne peut pas être justifiée.');
            }

            $justification = $attendance->addJustification(array_merge($data, [
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]));

            return $justification;
        });
    }

    public function approveJustification(int $justificationId, ?string $notes = null): Justification
    {
        $justification = Justification::findOrFail($justificationId);

        if (!$justification->canBeReviewed()) {
            throw new \Exception('Cette justification ne peut pas être approuvée.');
        }

        return $justification->approve(Auth::id(), $notes);
    }

    public function rejectJustification(int $justificationId, ?string $notes = null): Justification
    {
        $justification = Justification::findOrFail($justificationId);

        if (!$justification->canBeReviewed()) {
            throw new \Exception('Cette justification ne peut pas être rejetée.');
        }

        return $justification->reject(Auth::id(), $notes);
    }

    public function sendBulkAbsenceNotifications(string $date): int
    {
        $absences = $this->repository->getAbsencesByDate($date);
        $sentCount = 0;

        foreach ($absences as $absence) {
            try {
                $this->notifyParentAbsence($absence->student_id, $absence->session_id);
                $sentCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send absence notification", [
                    'student_id' => $absence->student_id,
                    'session_id' => $absence->session_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $sentCount;
    }

    public function getAttendanceTrends(int $classId, int $days = 30): array
    {
        $endDate = now();
        $startDate = now()->subDays($days);

        $attendances = $this->repository->query()
            ->whereHas('session', function ($q) use ($classId, $startDate, $endDate) {
                $q->where('class_id', $classId)
                  ->whereBetween('session_date', [$startDate, $endDate]);
            })
            ->with('session')
            ->get()
            ->groupBy(function ($attendance) {
                return $attendance->session->session_date->format('Y-m-d');
            });

        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayAttendances = $attendances->get($dateStr, collect());

            $total = $dayAttendances->count();
            $present = $dayAttendances->where('status', 'present')->count();

            $trends[] = [
                'date' => $dateStr,
                'total_students' => $total,
                'present_count' => $present,
                'absent_count' => $dayAttendances->where('status', 'absent')->count(),
                'late_count' => $dayAttendances->where('status', 'late')->count(),
                'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];

            $currentDate->addDay();
        }

        return $trends;
    }

    public function getStudentsAtRisk(int $classId, float $threshold = 75.0): Collection
    {
        $stats = $this->getClassAttendanceStats($classId);

        return $stats->filter(function ($stat) use ($threshold) {
            return $stat->attendance_rate < $threshold;
        })->sortBy('attendance_rate');
    }

    public function exportAttendanceData(int $classId, ?string $startDate = null, ?string $endDate = null): array
    {
        $attendances = $this->repository->query()
            ->whereHas('session', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->with(['student', 'session.subject', 'session.teacher', 'justification'])
            ->orderBy('session.session_date')
            ->orderBy('student.last_name');

        if ($startDate) {
            $attendances->whereHas('session', function ($q) use ($startDate) {
                $q->where('session_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $attendances->whereHas('session', function ($q) use ($endDate) {
                $q->where('session_date', '<=', $endDate);
            });
        }

        return $attendances->get()->map(function ($attendance) {
            return [
                'date' => $attendance->session->session_date->format('d/m/Y'),
                'subject' => $attendance->session->subject->name,
                'teacher' => $attendance->session->teacher->name,
                'student_matricule' => $attendance->student->matricule,
                'student_name' => $attendance->student->full_name,
                'status' => $attendance->status_label,
                'justified' => $attendance->justified ? 'Oui' : 'Non',
                'minutes_late' => $attendance->minutes_late ?? 0,
                'recorded_at' => $attendance->recorded_at?->format('d/m/Y H:i'),
            ];
        })->toArray();
    }

    private function notifyParentAbsence(int $studentId, int $sessionId): void
    {
        if (!$this->smsService) {
            return;
        }

        $student = Student::with('parents')->find($studentId);
        $session = Session::find($sessionId);

        if (!$student || $student->parents->isEmpty() || !$session) {
            return;
        }

        $primaryParent = $student->parents->firstWhere('pivot.is_primary', true)
                        ?? $student->parents->first();

        if ($primaryParent && $primaryParent->phone) {
            $message = sprintf(
                "Cher parent, votre enfant %s (%s) a été marqué(e) absent(e) en %s le %s. Collège ABC",
                $student->full_name,
                $student->matricule,
                $session->subject->name,
                $session->session_date->format('d/m/Y')
            );

            try {
                $this->smsService->send($primaryParent->phone, $message);
            } catch (\Exception $e) {
                Log::error("Failed to send absence SMS", [
                    'student_id' => $studentId,
                    'parent_id' => $primaryParent->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
