<?php

namespace Modules\Dashboard\Services;

use Modules\Student\Entities\Student;
use Modules\Academic\Entities\ClassRoom;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Entities\Invoice;
use Modules\Attendance\Entities\Attendance;
use Modules\Gradebook\Entities\Grade;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getGlobalStatistics(): array
    {
        return [
            'students' => [
                'total' => Student::count(),
                'active' => Student::where('status', 'active')->count(),
                'by_gender' => Student::select('gender', DB::raw('count(*) as count'))->groupBy('gender')->get(),
            ],
            'classes' => [
                'total' => ClassRoom::count(),
                'active' => ClassRoom::where('is_active', true)->count(),
                'average_students' => round(Student::count() / ClassRoom::count(), 2),
            ],
            'finances' => [
                'total_revenue' => Payment::where('status', 'validated')->sum('amount'),
                'pending_amount' => Invoice::whereIn('status', ['issued', 'partially_paid'])->sum('due_amount'),
                'this_month_revenue' => Payment::where('status', 'validated')
                                              ->whereMonth('payment_date', now()->month)
                                              ->sum('amount'),
            ],
            'attendance' => [
                'today_rate' => $this->getTodayAttendanceRate(),
                'month_rate' => $this->getMonthAttendanceRate(),
                'total_absences' => Attendance::where('status', 'absent')->whereMonth('date', now()->month)->count(),
            ],
            'academics' => [
                'average_grade' => round(Grade::avg('score'), 2),
                'passing_rate' => $this->getGlobalPassingRate(),
                'total_evaluations' => \Modules\Gradebook\Entities\Evaluation::count(),
            ],
        ];
    }

    public function getRecentActivity(int $limit = 10): array
    {
        $recentPayments = Payment::with('student')->latest()->limit($limit)->get()
                                ->map(fn($p) => ['type' => 'payment', 'data' => $p, 'date' => $p->created_at]);
        
        $recentGrades = Grade::with(['student', 'evaluation'])->latest()->limit($limit)->get()
                            ->map(fn($g) => ['type' => 'grade', 'data' => $g, 'date' => $g->created_at]);

        return collect($recentPayments)->merge($recentGrades)->sortByDesc('date')->take($limit)->values()->all();
    }

    public function getAttendanceTrend(int $days = 30): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = Attendance::whereDate('date', $date)->count();
            $present = Attendance::whereDate('date', $date)->whereIn('status', ['present', 'excused'])->count();
            
            $data[] = [
                'date' => $date,
                'rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        }
        return $data;
    }

    public function getGradeDistribution(): array
    {
        return [
            'A (18-20)' => Grade::whereBetween('score', [18, 20])->count(),
            'B (16-18)' => Grade::whereBetween('score', [16, 18])->count(),
            'C (14-16)' => Grade::whereBetween('score', [14, 16])->count(),
            'D (12-14)' => Grade::whereBetween('score', [12, 14])->count(),
            'E (10-12)' => Grade::whereBetween('score', [10, 12])->count(),
            'F (0-10)' => Grade::where('score', '<', 10)->count(),
        ];
    }

    public function getFinancialTrend(int $months = 6): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Payment::where('status', 'validated')
                             ->whereYear('payment_date', $month->year)
                             ->whereMonth('payment_date', $month->month)
                             ->sum('amount');
            
            $data[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
            ];
        }
        return $data;
    }

    public function getTopPerformers(int $limit = 10)
    {
        return Student::with('currentEnrollment.classRoom')
                     ->get()
                     ->map(function($student) {
                         $average = Grade::byStudent($student->id)->avg('score');
                         return ['student' => $student, 'average' => $average];
                     })
                     ->sortByDesc('average')
                     ->take($limit)
                     ->values();
    }

    protected function getTodayAttendanceRate(): float
    {
        $total = Attendance::whereDate('date', today())->count();
        if ($total === 0) return 0;
        
        $present = Attendance::whereDate('date', today())->whereIn('status', ['present', 'excused'])->count();
        return round(($present / $total) * 100, 2);
    }

    protected function getMonthAttendanceRate(): float
    {
        $total = Attendance::whereMonth('date', now()->month)->count();
        if ($total === 0) return 0;
        
        $present = Attendance::whereMonth('date', now()->month)->whereIn('status', ['present', 'excused'])->count();
        return round(($present / $total) * 100, 2);
    }

    protected function getGlobalPassingRate(): float
    {
        $total = Grade::count();
        if ($total === 0) return 0;
        
        $passing = Grade::where('score', '>=', 10)->count();
        return round(($passing / $total) * 100, 2);
    }
}
