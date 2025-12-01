<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Repositories\AcademicYearRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AcademicYearService
{
    public function __construct(
        private AcademicYearRepository $repository
    ) {}

    public function createAcademicYear(array $data): AcademicYear
    {
        $year = AcademicYear::create([
            'name' => $data['name'] ?? $this->generateName($data['start_date'], $data['end_date']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'planned',
            'is_current' => $data['is_current'] ?? false,
            'description' => $data['description'] ?? null,
            'semesters' => $data['semesters'] ?? $this->generateSemesters($data['start_date'], $data['end_date']),
        ]);

        return $year->fresh();
    }

    public function updateAcademicYear(int $id, array $data): AcademicYear
    {
        $year = $this->findAcademicYear($id);

        $updateData = collect($data)->only([
            'name', 'start_date', 'end_date', 'status', 'is_current', 'description', 'semesters'
        ])->toArray();

        $year->update($updateData);

        return $year->fresh();
    }

    public function findAcademicYear(int $id): AcademicYear
    {
        return $this->repository->findOrFail($id);
    }

    public function getAcademicYears(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->repository->query();

        // Appliquer les filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_current'])) {
            $query->where('is_current', $filters['is_current']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->with(['enrollments'])
                    ->orderBy('start_date', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function setCurrentYear(int $yearId): AcademicYear
    {
        $year = $this->findAcademicYear($yearId);

        if ($year->status === 'completed') {
            throw new \Exception('Cannot set a completed academic year as current');
        }

        return $year->setAsCurrent();
    }

    public function completeYear(int $yearId): AcademicYear
    {
        $year = $this->findAcademicYear($yearId);
        return $year->complete();
    }

    public function getCurrentYear(): ?AcademicYear
    {
        return AcademicYear::getCurrentYear();
    }

    public function getActiveYears(): Collection
    {
        return $this->repository->getByStatus('active');
    }

    public function getYearsStats(): array
    {
        $total = AcademicYear::count();
        $current = AcademicYear::current()->count();
        $active = AcademicYear::active()->count();
        $completed = AcademicYear::where('status', 'completed')->count();
        $planned = AcademicYear::where('status', 'planned')->count();

        $byStatus = [
            'planned' => $planned,
            'active' => $active,
            'completed' => $completed,
            'cancelled' => $total - $planned - $active - $completed,
        ];

        return [
            'total' => $total,
            'current' => $current,
            'active' => $active,
            'completed' => $completed,
            'planned' => $planned,
            'by_status' => $byStatus,
        ];
    }

    public function generateSemesters(Carbon $startDate, Carbon $endDate): array
    {
        $totalWeeks = $startDate->diffInWeeks($endDate);
        $semesterLength = ceil($totalWeeks / 2); // Diviser en 2 semestres

        return [
            [
                'name' => 'Semestre 1',
                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate->copy()->addWeeks($semesterLength)->toDateString(),
                'number' => 1,
            ],
            [
                'name' => 'Semestre 2',
                'start_date' => $startDate->copy()->addWeeks($semesterLength)->toDateString(),
                'end_date' => $endDate->toDateString(),
                'number' => 2,
            ],
        ];
    }

    private function generateName(Carbon $startDate, Carbon $endDate): string
    {
        return $startDate->format('Y') . '-' . $endDate->format('Y');
    }

    public function isYearOngoing(AcademicYear $year): bool
    {
        return $year->is_ongoing;
    }

    public function getYearProgress(AcademicYear $year): float
    {
        return $year->progress_percentage;
    }

    public function canSetAsCurrent(AcademicYear $year): bool
    {
        return $year->status === 'planned' || $year->status === 'active';
    }

    public function getNextAcademicYear(): ?AcademicYear
    {
        return AcademicYear::where('status', 'planned')
                          ->where('start_date', '>', now())
                          ->orderBy('start_date')
                          ->first();
    }

    public function getPreviousAcademicYear(): ?AcademicYear
    {
        $current = $this->getCurrentYear();
        if (!$current) return null;

        return AcademicYear::where('end_date', '<', $current->start_date)
                          ->orderBy('end_date', 'desc')
                          ->first();
    }
}
