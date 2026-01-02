<?php

namespace Modules\Grade\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Searchable;
use Modules\Grade\Entities\Grade;
use Modules\Grade\Entities\Evaluation;

class GradeRepository
{
    use Searchable;

    protected $model;

    public function __construct(Grade $grade)
    {
        $this->model = $grade;
    }

    // CRUD operations
    public function create(array $data): Grade
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?Grade
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Grade
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function update(Grade $grade, array $data): bool
    {
        return $grade->update($data);
    }

    public function delete(Grade $grade): bool
    {
        return $grade->delete();
    }

    public function restore(int $id): bool
    {
        return $this->model->withTrashed()->find($id)?->restore() ?? false;
    }

    public function forceDelete(int $id): bool
    {
        return $this->model->withTrashed()->find($id)?->forceDelete() ?? false;
    }

    // Query methods
    public function getByStudent(int $studentId): Collection
    {
        return $this->model->byStudent($studentId)->get();
    }

    public function getByEvaluation(int $evaluationId): Collection
    {
        return $this->model->byEvaluation($evaluationId)->get();
    }

    public function getByStudentAndSubject(int $studentId, int $subjectId): Collection
    {
        return $this->model->byStudent($studentId)->bySubject($subjectId)->get();
    }

    public function getByClassAndSubject(int $classId, int $subjectId, int $academicYearId = null): Collection
    {
        $grades = $this->model->byClass($classId)->bySubject($subjectId);

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        return $grades->get();
    }

    public function getStudentGradesForPeriod(int $studentId, string $period, int $academicYearId = null): Collection
    {
        $grades = $this->model->byStudent($studentId)
            ->whereHas('evaluation', function ($q) use ($period) {
                $q->where('period', $period);
            });

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        return $grades->get();
    }

    public function getClassGradesForEvaluation(int $evaluationId): Collection
    {
        return $this->model->byEvaluation($evaluationId)
            ->with(['student', 'evaluation'])
            ->orderBy('student.last_name')
            ->orderBy('student.first_name')
            ->get();
    }

    public function getAbsentGrades(array $filters = []): Collection
    {
        $query = $this->model->absent();

        if (isset($filters['evaluation_id'])) {
            $query->byEvaluation($filters['evaluation_id']);
        }

        if (isset($filters['class_id'])) {
            $query->byClass($filters['class_id']);
        }

        return $query->get();
    }

    // Statistics methods
    public function getAverageByEvaluation(int $evaluationId): float
    {
        return $this->model->byEvaluation($evaluationId)
            ->present()
            ->avg('weighted_score') ?? 0;
    }

    public function getAverageByStudent(int $studentId, int $academicYearId = null): float
    {
        $grades = $this->model->byStudent($studentId)->present();

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        return $grades->avg('weighted_score') ?? 0;
    }

    public function getAverageByClassAndSubject(int $classId, int $subjectId, int $academicYearId = null): float
    {
        $grades = $this->model->byClass($classId)->bySubject($subjectId)->present();

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        return $grades->with(['evaluation'])->get()->avg('weighted_score') ?? 0;
    }

    public function getGradeDistribution(int $evaluationId): array
    {
        $grades = $this->model->byEvaluation($evaluationId)->present()->get();

        $distribution = [
            'A+' => 0,
            'A' => 0,
            'B+' => 0,
            'B' => 0,
            'C+' => 0,
            'C' => 0,
            'D+' => 0,
            'D' => 0,
            'F' => 0
        ];

        foreach ($grades as $grade) {
            if (isset($distribution[$grade->grade_letter])) {
                $distribution[$grade->grade_letter]++;
            }
        }

        return $distribution;
    }

    public function getPassingRate(array $filters = []): float
    {
        $query = $this->model->present();

        if (isset($filters['evaluation_id'])) {
            $query->byEvaluation($filters['evaluation_id']);
        }

        if (isset($filters['class_id'])) {
            $query->byClass($filters['class_id']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->byAcademicYear($filters['academic_year_id']);
        }

        $totalGrades = $query->count();
        if ($totalGrades === 0) return 0;

        $passingGrades = $query->where('weighted_score', '>=', 10)->count();

        return round(($passingGrades / $totalGrades) * 100, 1);
    }

    // Bulk operations
    public function bulkCreate(array $gradesData): Collection
    {
        $grades = [];
        foreach ($gradesData as $data) {
            $grades[] = $this->create($data);
        }
        return collect($grades);
    }

    public function bulkUpdate(array $updates): int
    {
        $updatedCount = 0;
        foreach ($updates as $gradeId => $data) {
            $grade = $this->findById($gradeId);
            if ($grade && $grade->update($data)) {
                $updatedCount++;
            }
        }
        return $updatedCount;
    }

    // Validation methods
    public function studentHasGradeForEvaluation(int $studentId, int $evaluationId): bool
    {
        return $this->model->where('student_id', $studentId)
            ->where('evaluation_id', $evaluationId)
            ->exists();
    }

    public function canRecordGrade(int $evaluationId, int $studentId, $teacherId): bool
    {
        // Check if teacher teaches this subject in this class
        $evaluation = Evaluation::find($evaluationId);

        if (!$evaluation) return false;

        // Check if student belongs to class
        $isEnrolled = $evaluation->class->enrollments()->where('student_id', $studentId)->exists();
        if (!$isEnrolled) return false;

        // Check user permissions
        // Use a lightweight query or assume $teacherId is currently authenticated user ID

        // 1. If user is the assigned teacher (use == for type-agnostic comparison since teacher_id is UUID)
        if ($evaluation->teacher_id == $teacherId) {
            return true;
        }

        // 2. If user is admin/manager
        $user = \Modules\Core\Entities\User::find($teacherId);
        if ($user && ($user->hasRole('super_admin') || $user->can('manage-academic') || $user->can('manage-grades'))) {
            return true;
        }

        return false;
    }

    // Search and filter methods
    public function search(array $filters = [], int $perPage = 15)
    {
        $query = $this->model->with(['student', 'evaluation.subject', 'evaluation.teacher', 'recorder']);

        // Apply filters
        if (isset($filters['student_id'])) {
            $query->byStudent($filters['student_id']);
        }

        if (isset($filters['evaluation_id'])) {
            $query->byEvaluation($filters['evaluation_id']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->byAcademicYear($filters['academic_year_id']);
        }

        if (isset($filters['subject_id'])) {
            $query->bySubject($filters['subject_id']);
        }

        if (isset($filters['class_id'])) {
            $query->byClass($filters['class_id']);
        }

        if (isset($filters['is_absent'])) {
            $filters['is_absent'] ? $query->absent() : $query->present();
        }

        if (isset($filters['grade_from'])) {
            $query->where('weighted_score', '>=', $filters['grade_from']);
        }

        if (isset($filters['grade_to'])) {
            $query->where('weighted_score', '<=', $filters['grade_to']);
        }

        if (isset($filters['recorded_from'])) {
            $query->whereDate('recorded_at', '>=', $filters['recorded_from']);
        }

        if (isset($filters['recorded_to'])) {
            $query->whereDate('recorded_at', '<=', $filters['recorded_to']);
        }

        // Apply search
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        if (isset($filters['per_page'])) {
            return $query->paginate($filters['per_page']);
        }

        return $query->paginate($perPage);
    }
}
