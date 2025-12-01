<?php

namespace Modules\Attendance\Repositories;

use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\Session;
use Modules\Attendance\Entities\Justification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRepository
{
    public function __construct(
        private Attendance $model
    ) {}

    public function find(int $id): ?Attendance
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Attendance
    {
        return $this->model->findOrFail($id);
    }

    public function findByUuid(string $uuid): ?Attendance
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): Attendance
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Attendance
    {
        $attendance = $this->findOrFail($id);
        $attendance->update($data);
        return $attendance->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->delete();
    }

    public function query()
    {
        return $this->model->query();
    }

    // Méthodes spécifiques aux présences
    public function markAttendance(int $studentId, int $sessionId, array $data, ?int $recordedBy = null): Attendance
    {
        return $this->model->updateOrCreate(
            [
                'student_id' => $studentId,
                'session_id' => $sessionId,
            ],
            array_merge($data, [
                'recorded_at' => now(),
                'recorded_by' => $recordedBy,
            ])
        );
    }

    public function bulkMarkAttendance(int $sessionId, array $attendanceData): Collection
    {
        $attendances = collect();

        foreach ($attendanceData as $data) {
            $attendance = $this->markAttendance(
                $data['student_id'],
                $sessionId,
                $data
            );
            $attendances->push($attendance);
        }

        return $attendances;
    }

    public function getByStudent(int $studentId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->where('student_id', $studentId)
            ->with(['session.subject', 'session.class', 'justification'])
            ->orderBy('recorded_at', 'desc');

        if ($startDate) {
            $query->whereHas('session', function ($q) use ($startDate) {
                $q->where('session_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('session', function ($q) use ($endDate) {
                $q->where('session_date', '<=', $endDate);
            });
        }

        return $query->get();
    }

    public function getBySession(int $sessionId): Collection
    {
        return $this->model->where('session_id', $sessionId)
            ->with(['student.user', 'recordedBy', 'justification'])
            ->orderBy('student_id')
            ->get();
    }

    public function getByClassAndDate(int $classId, string $date): Collection
    {
        return $this->model->whereHas('session', function ($q) use ($classId, $date) {
            $q->where('class_id', $classId)
              ->where('session_date', $date);
        })
        ->with(['student.user', 'session.subject', 'justification'])
        ->get();
    }

    public function getAbsencesByDate(string $date): Collection
    {
        return $this->model->where('status', 'absent')
            ->whereHas('session', function ($q) use ($date) {
                $q->where('session_date', $date);
            })
            ->with(['student.parents', 'session'])
            ->get();
    }

    public function getMonthlyReport(int $classId, int $month, int $year): Collection
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return $this->model->whereHas('session', function ($q) use ($classId, $startDate, $endDate) {
            $q->where('class_id', $classId)
              ->whereBetween('session_date', [$startDate, $endDate]);
        })
        ->with(['student', 'session'])
        ->get()
        ->groupBy('student_id');
    }

    public function getAttendanceStats(int $studentId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->model->where('student_id', $studentId);

        if ($startDate) {
            $query->whereHas('session', function ($q) use ($startDate) {
                $q->where('session_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('session', function ($q) use ($endDate) {
                $q->where('session_date', '<=', $endDate);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_sessions,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excused_count,
            SUM(CASE WHEN justified = true THEN 1 ELSE 0 END) as justified_count
        ')->first();

        $attendanceRate = $stats->total_sessions > 0
            ? round(($stats->present_count / $stats->total_sessions) * 100, 2)
            : 0;

        return [
            'total_sessions' => $stats->total_sessions ?? 0,
            'present_count' => $stats->present_count ?? 0,
            'absent_count' => $stats->absent_count ?? 0,
            'late_count' => $stats->late_count ?? 0,
            'excused_count' => $stats->excused_count ?? 0,
            'justified_count' => $stats->justified_count ?? 0,
            'attendance_rate' => $attendanceRate,
        ];
    }

    public function getClassAttendanceStats(int $classId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->whereHas('session', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        });

        if ($startDate) {
            $query->whereHas('session', function ($q) use ($startDate) {
                $q->where('session_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('session', function ($q) use ($endDate) {
                $q->where('session_date', '<=', $endDate);
            });
        }

        return $query->selectRaw('
            student_id,
            COUNT(*) as total_sessions,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excused_count,
            ROUND((SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_rate
        ')
        ->with('student:id,first_name,last_name,matricule')
        ->groupBy('student_id')
        ->orderBy('attendance_rate', 'desc')
        ->get();
    }

    public function getAttendanceRate(int $studentId, ?string $startDate = null, ?string $endDate = null): float
    {
        $stats = $this->getAttendanceStats($studentId, $startDate, $endDate);
        return $stats['attendance_rate'];
    }

    public function getUnjustifiedAbsences(int $studentId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->where('student_id', $studentId)
            ->where('status', 'absent')
            ->where('justified', false);

        if ($startDate) {
            $query->whereHas('session', function ($q) use ($startDate) {
                $q->where('session_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('session', function ($q) use ($endDate) {
                $q->where('session_date', '<=', $endDate);
            });
        }

        return $query->with(['session.subject', 'session.class'])->get();
    }

    public function getLateArrivals(int $studentId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->model->where('student_id', $studentId)
            ->where('status', 'late');

        if ($startDate) {
            $query->whereHas('session', function ($q) use ($startDate) {
                $q->where('session_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('session', function ($q) use ($endDate) {
                $q->where('session_date', '<=', $endDate);
            });
        }

        return $query->with(['session.subject', 'session.class'])
            ->orderBy('minutes_late', 'desc')
            ->get();
    }
}
