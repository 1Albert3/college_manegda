<?php

namespace Modules\Gradebook\Services;

use Modules\Gradebook\Entities\Evaluation;
use Modules\Gradebook\Entities\Grade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradebookService
{
    public function createEvaluation(array $data): Evaluation
    {
        try {
            DB::beginTransaction();
            $evaluation = Evaluation::create($data);
            DB::commit();
            Log::info('Evaluation created', ['id' => $evaluation->id]);
            return $evaluation->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create evaluation', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function recordGrade(array $data): Grade
    {
        try {
            DB::beginTransaction();

            $existing = Grade::where('evaluation_id', $data['evaluation_id'])
                            ->where('student_id', $data['student_id'])
                            ->first();

            if ($existing) throw new \Exception('Note déjà existante pour cet élève');

            $data['graded_by'] = $data['graded_by'] ?? auth()->id();
            $grade = Grade::create($data);

            DB::commit();
            Log::info('Grade recorded', ['id' => $grade->id]);
            return $grade->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to record grade', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function bulkRecordGrades(int $evaluationId, array $studentScores): array
    {
        try {
            DB::beginTransaction();
            $grades = [];

            foreach ($studentScores as $studentId => $score) {
                try {
                    $grades[] = $this->recordGrade([
                        'evaluation_id' => $evaluationId,
                        'student_id' => $studentId,
                        'score' => $score['score'],
                        'comment' => $score['comment'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed grade for student', ['student_id' => $studentId]);
                }
            }

            DB::commit();
            Log::info('Bulk grades recorded', ['count' => count($grades)]);
            return $grades;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getStudentAverage(int $studentId, ?int $subjectId = null, ?int $semesterId = null): float
    {
        $query = Grade::byStudent($studentId)->with('evaluation');

        if ($subjectId) {
            $query->whereHas('evaluation', fn($q) => $q->where('subject_id', $subjectId));
        }

        if ($semesterId) {
            $query->whereHas('evaluation', fn($q) => $q->where('semester_id', $semesterId));
        }

        return $query->avg('weighted_score') ?? 0;
    }

    public function getClassStatistics(int $evaluationId): array
    {
        $grades = Grade::where('evaluation_id', $evaluationId)->pluck('score');

        return [
            'count' => $grades->count(),
            'average' => round($grades->avg(), 2),
            'min' => $grades->min(),
            'max' => $grades->max(),
            'passing_count' => $grades->filter(fn($s) => $s >= 10)->count(),
            'passing_rate' => $grades->count() > 0 ? round(($grades->filter(fn($s) => $s >= 10)->count() / $grades->count()) * 100, 2) : 0,
        ];
    }

    public function generateReportCard(int $studentId, int $semesterId): array
    {
        $grades = Grade::byStudent($studentId)
                      ->whereHas('evaluation', fn($q) => $q->where('semester_id', $semesterId))
                      ->with(['evaluation.subject'])
                      ->get()
                      ->groupBy('evaluation.subject.name');

        $report = [];
        foreach ($grades as $subject => $subjectGrades) {
            $report[$subject] = [
                'grades' => $subjectGrades,
                'average' => round($subjectGrades->avg('weighted_score'), 2),
                'count' => $subjectGrades->count(),
            ];
        }

        $overall = collect($report)->avg('average');

        return [
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'subjects' => $report,
            'overall_average' => round($overall, 2),
            'is_passing' => $overall >= 10,
        ];
    }
}
