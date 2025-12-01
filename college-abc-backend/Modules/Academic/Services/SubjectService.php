<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\Subject;
use Modules\Academic\Repositories\SubjectRepository;
use Illuminate\Support\Collection;

class SubjectService
{
    public function __construct(
        private SubjectRepository $repository
    ) {}

    public function createSubject(array $data): Subject
    {
        // Générer code automatiquement si non fourni
        if (!isset($data['code'])) {
            $data['code'] = $this->generateCode($data['name']);
        }

        return $this->repository->create($data);
    }

    public function updateSubject(int $id, array $data): Subject
    {
        $subject = $this->findSubject($id);

        // Régénérer code si nom changé
        if (isset($data['name']) && !isset($data['code'])) {
            $data['code'] = $this->generateCode($data['name']);
        }

        $this->repository->update($subject, $data);

        return $subject->fresh();
    }

    public function findSubject(int $id): Subject
    {
        return $this->repository->findOrFail($id);
    }

    public function findByCode(string $code): Subject
    {
        return $this->repository->findByCodeOrFail($code);
    }

    public function deleteSubject(int $id): bool
    {
        $subject = $this->findSubject($id);
        return $this->repository->delete($subject);
    }

    public function getSubjects(array $filters = [], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->repository->query();

        // Appliquer les filtres
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['level_type'])) {
            $query->where('level_type', $filters['level_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Relations par défaut
        $defaultRelations = [];
        $relations = array_unique(array_merge($defaultRelations, $relations));

        return $query->with($relations)
                    ->latest()
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function assignToClass(int $subjectId, int $classId, array $attributes = []): bool
    {
        $subject = $this->findSubject($subjectId);
        $subject->assignToClass($classId, $attributes);

        return true;
    }

    public function removeFromClass(int $subjectId, int $classId): bool
    {
        $subject = $this->findSubject($subjectId);
        $subject->classes()->detach($classId);

        return true;
    }

    public function assignTeacher(int $subjectId, int $teacherId, int $academicYearId = null): Subject
    {
        $subject = $this->findSubject($subjectId);

        return $subject->assignTeacher($teacherId, $academicYearId);
    }

    public function getSubjectsByCategory(string $category): Collection
    {
        return $this->repository->getByCategory($category);
    }

    public function getSubjectsByLevelType(string $levelType): Collection
    {
        return $this->repository->getByLevelType($levelType);
    }

    public function getActiveSubjects(): Collection
    {
        return $this->repository->getActive();
    }

    public function getSubjectsStats(): array
    {
        $total = $this->repository->count();
        $active = $this->repository->getActive()->count();
        $inactive = $total - $active;

        $byCategory = $this->repository->countByCategory();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_category' => $byCategory,
        ];
    }

    public function bulkActivate(array $subjectIds): int
    {
        return $this->repository->bulkUpdate($subjectIds, ['is_active' => true]);
    }

    public function bulkDeactivate(array $subjectIds): int
    {
        return $this->repository->bulkUpdate($subjectIds, ['is_active' => false]);
    }

    public function getSubjectsGroupedByCategory(): Collection
    {
        return Subject::getByCategoryGrouped();
    }

    public function updateCoefficients(array $subjectCoefficientPairs): int
    {
        $updated = 0;

        foreach ($subjectCoefficientPairs as $subjectId => $coefficient) {
            $subject = $this->findSubject($subjectId);
            $subject->update(['coefficients' => $coefficient]);
            $updated++;
        }

        return $updated;
    }

    private function generateCode(string $name): string
    {
        // Prendre les 3 premières lettres en majuscules
        $code = strtoupper(substr($this->normalizeString($name), 0, 3));

        // Vérifier si le code existe déjà et générer une variante si nécessaire
        $originalCode = $code;
        $counter = 1;

        while ($this->repository->query()->where('code', $code)->exists()) {
            $code = $originalCode . $counter;
            $counter++;
        }

        return $code;
    }

    private function normalizeString(string $string): string
    {
        // Supprimer accents et caractères spéciaux
        $string = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string);
        // Garder seulement les lettres
        $string = preg_replace('/[^a-z]/', '', $string);

        return $string;
    }

    public function validateSubjectData(array $data): array
    {
        $errors = [];

        // Vérifier si le code existe déjà (pour création)
        if (isset($data['code'])) {
            $existing = $this->repository->findByCode($data['code']);
            if ($existing) {
                $errors[] = "Le code de matière '{$data['code']}' existe déjà.";
            }
        }

        // Vérifier que la catégorie est valide
        $validCategories = [
            'sciences', 'literature', 'language', 'social_studies',
            'arts', 'physical_education', 'technology', 'other'
        ];

        if (isset($data['category']) && !in_array($data['category'], $validCategories)) {
            $errors[] = "La catégorie '{$data['category']}' n'est pas valide.";
        }

        // Vérifier que le type de niveau est valide
        $validLevels = ['primary', 'secondary', 'both'];

        if (isset($data['level_type']) && !in_array($data['level_type'], $validLevels)) {
            $errors[] = "Le type de niveau '{$data['level_type']}' n'est pas valide.";
        }

        return $errors;
    }
}
