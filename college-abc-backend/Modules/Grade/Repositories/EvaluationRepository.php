<?php

namespace Modules\Grade\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Grade\Entities\Evaluation;

class EvaluationRepository
{
    protected $model;

    public function __construct(Evaluation $evaluation)
    {
        $this->model = $evaluation;
    }

    // CRUD operations
    public function create(array $data): Evaluation
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Evaluation
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Evaluation
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function update(Evaluation $evaluation, array $data): bool
    {
        return $evaluation->update($data);
    }

    public function delete(Evaluation $evaluation): bool
    {
        return $evaluation->delete();
    }

    // Query methods
    public function getByTeacher(int $teacherId): Collection
    {
        return $this->model->where('teacher_id', $teacherId)->get();
    }

    public function getByClass(int $classId): Collection
    {
        return $this->model->where('class_id', $classId)->get();
    }

    public function getBySubject(int $subjectId): Collection
    {
        return $this->model->where('subject_id', $subjectId)->get();
    }

    public function getByAcademicYear(int $academicYearId): Collection
    {
        return $this->model->where('academic_year_id', $academicYearId)->get();
    }

    public function getByPeriod(string $period, int $academicYearId = null): Collection
    {
        $query = $this->model->where('period', $period);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->get();
    }

    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function getUpcoming(int $days = 7): Collection
    {
        return $this->model->upcoming()
            ->where('evaluation_date', '<=', now()->addDays($days))
            ->orderBy('evaluation_date')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate, array $filters = []): Collection
    {
        $query = $this->model->whereBetween('evaluation_date', [$startDate, $endDate]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        return $query->get();
    }

    // Statistics methods
    public function getCompletionStats(int $academicYearId = null): array
    {
        $query = $this->model;

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $total = $query->count();
        $completed = $query->completed()->count();
        $ongoing = $query->ongoing()->count();
        $planned = $query->where('status', 'planned')->count();
        $cancelled = $query->where('status', 'cancelled')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'ongoing' => $ongoing,
            'planned' => $planned,
            'cancelled' => $cancelled,
            'completion_percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    public function getEvaluationsBySubjectStats(int $academicYearId = null): array
    {
        $query = $this->model->with('subject');

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $stats = [];
        $evaluations = $query->get();

        foreach ($evaluations->groupBy('subject.name') as $subjectName => $subjectEvaluations) {
            $stats[$subjectName] = [
                'count' => $subjectEvaluations->count(),
                'completed' => $subjectEvaluations->completed()->count(),
                'average_coefficient' => $subjectEvaluations->avg('coefficient'),
            ];
        }

        return $stats;
    }

    public function getTeacherWorkload(int $teacherId, int $academicYearId = null): array
    {
        $query = $this->model->where('teacher_id', $teacherId);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $evaluations = $query->get();

        return [
            'total_evaluations' => $evaluations->count(),
            'completed_evaluations' => $evaluations->completed()->count(),
            'total_students' => $evaluations->sum(function ($evaluation) {
                return $evaluation->class->enrollments()->active()->count();
            }),
            'subjects_count' => $evaluations->pluck('subject_id')->unique()->count(),
            'classes_count' => $evaluations->pluck('class_id')->unique()->count(),
        ];
    }

    // Bulk operations
    public function bulkCreate(array $evaluationsData): Collection
    {
        $evaluations = [];
        foreach ($evaluationsData as $data) {
            $evaluations[] = $this->create($data);
        }
        return collect($evaluations);
    }

    public function bulkUpdateStatus(array $evaluationIds, string $status): int
    {
        return $this->model->whereIn('id', $evaluationIds)->update(['status' => $status]);
    }

    // Validation methods
    public function isTeacherAssignedToSubjectAndClass(int $teacherId, int $subjectId, int $classId, int $academicYearId): bool
    {
        return \Modules\Academic\Entities\TeacherSubject::where('teacher_id', $teacherId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->whereHas('teacher.classes', function ($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->exists();
    }

    public function hasConflictingSchedule(int $teacherId, string $evaluationDate, int $excludeId = null): bool
    {
        $query = $this->model->where('teacher_id', $teacherId)
            ->where('evaluation_date', $evaluationDate);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function canStartEvaluation(int $evaluationId): bool
    {
        $evaluation = $this->findById($evaluationId);

        if (!$evaluation || $evaluation->status !== 'planned') {
            return false;
        }

        return $evaluation->evaluation_date <= now()->toDateString();
    }

    // Search and filter methods
    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with(['subject', 'class', 'teacher', 'academicYear']);

        // Apply filters
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (isset($filters['teacher_id'])) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('evaluation_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('evaluation_date', '<=', $filters['date_to']);
        }

        // Apply search
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'evaluation_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if (isset($filters['per_page'])) {
            return $query->paginate($filters['per_page']);
        }

        return $query->paginate($perPage);
    }

    // Report methods
    public function getEvaluationReport(int $evaluationId): array
    {
        $evaluation = $this->model->with(['grades.student', 'subject', 'class', 'teacher'])->find($evaluationId);

        if (!$evaluation) {
            return [];
        }

        $grades = $evaluation->grades;
        $presentGrades = $grades->where('is_absent', false);
        $absentCount = $grades->where('is_absent', true)->count();

        return [
            'evaluation' => $evaluation,
            'total_students' => $evaluation->class->enrollments()->active()->count(),
            'graded_students' => $grades->count(),
            'absent_students' => $absentCount,
            'present_grades' => $presentGrades->values(),
            'average_score' => $presentGrades->avg('score'),
            'average_weighted_score' => $presentGrades->avg('weighted_score'),
            'minimum_score' => $presentGrades->min('score'),
            'maximum_score' => $presentGrades->max('score'),
            'passing_rate' => $presentGrades->where('weighted_score', '>=', 10)->count() / max($presentGrades->count(), 1) * 100,
            'grade_distribution' => $evaluation->getGradeDistribution(),
            'completion_percentage' => $evaluation->completion_percentage,
        ];
    }
}
