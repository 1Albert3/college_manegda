<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Repositories\ClassRoomRepository;
use Illuminate\Support\Collection;

class ClassRoomService
{
    public function __construct(
        private ClassRoomRepository $repository
    ) {}

    public function createClassRoom(array $data): ClassRoom
    {
        // Générer nom automatiquement si non fourni
        if (!isset($data['name'])) {
            $data['name'] = $this->generateName($data['level'] ?? 'Classe', $data['stream'] ?? null);
        }

        $classRoom = $this->repository->create($data);
        return $classRoom->fresh();
    }

    public function updateClassRoom(int $id, array $data): ClassRoom
    {
        $classRoom = $this->findClassRoom($id);

        $this->repository->update($classRoom, $data);

        return $classRoom->fresh();
    }

    public function findClassRoom(int $id): ClassRoom
    {
        return $this->repository->findOrFail($id);
    }

    public function findByName(string $name): ClassRoom
    {
        return $this->repository->findByName($name) ??
               throw new \Exception("Classe '{$name}' introuvable");
    }

    public function deleteClassRoom(int $id): bool
    {
        $classRoom = $this->findClassRoom($id);

        // Vérifier que la classe n'a pas d'étudiants actifs
        if ($classRoom->current_students_count > 0) {
            throw new \Exception('Impossible de supprimer une classe avec des étudiants actifs');
        }

        return $this->repository->delete($classRoom);
    }

    public function getClassRooms(array $filters = [], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->repository->query();

        // Appliquer les filtres
        if (isset($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (isset($filters['stream'])) {
            $query->where('stream', $filters['stream']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Relations par défaut
        $defaultRelations = ['subjects'];
        $relations = array_unique(array_merge($defaultRelations, $relations));

        return $query->with($relations)
                    ->orderByLevel()
                    ->paginate($filters['per_page'] ?? 15);
    }

    public function assignSubject(int $classId, int $subjectId, array $attributes = []): bool
    {
        $classRoom = $this->findClassRoom($classId);
        $classRoom->assignSubject($subjectId, $attributes);

        return true;
    }

    public function removeSubject(int $classId, int $subjectId): bool
    {
        $classRoom = $this->findClassRoom($classId);
        $classRoom->removeSubject($subjectId);

        return true;
    }

    public function enrollStudent(int $classId, int $studentId, array $enrollmentData = []): \Modules\Student\Entities\Enrollment
    {
        $classRoom = $this->findClassRoom($classId);

        // Pour l'instant, créer l'enrollment directement
        // TODO: Intégrer avec StudentService quand il sera disponible
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);

        return $classRoom->enrollStudent($student, $enrollmentData);
    }

    public function updateStudentsCount(int $classId): ClassRoom
    {
        $classRoom = $this->findClassRoom($classId);
        return $classRoom->updateStudentsCount();
    }

    public function getClassRoomsByLevel(string $level): Collection
    {
        return $this->repository->getByLevel($level);
    }

    public function getClassRoomsByStream(string $stream): Collection
    {
        return $this->repository->getByStream($stream);
    }

    public function getActiveClassRooms(): Collection
    {
        return $this->repository->getActive();
    }

    public function getClassRoomsStats(): array
    {
        $total = $this->repository->count();
        $active = $this->repository->getActive()->count();
        $inactive = $total - $active;

        $byLevel = $this->repository->countByLevel();

        $totalCapacity = ClassRoom::where('status', 'active')->sum('capacity');
        $totalStudents = ClassRoom::where('status', 'active')->sum('current_students_count');
        $occupancyRate = $totalCapacity > 0 ? round(($totalStudents / $totalCapacity) * 100, 1) : 0;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_level' => $byLevel,
            'total_capacity' => $totalCapacity,
            'total_students' => $totalStudents,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    public function getClassRoomsGroupedByLevel(): Collection
    {
        return collect($this->repository->getByLevelGrouped());
    }

    public function getAttendanceStats(int $classId): array
    {
        $classRoom = $this->findClassRoom($classId);
        return $classRoom->getAttendanceStats();
    }

    private function generateName(string $level, ?string $stream = null): string
    {
        $baseName = $level;

        if ($stream) {
            $baseName .= ' ' . $stream;
        }

        // Ajouter numéro si nécessaire pour éviter les doublons
        $counter = 1;
        $name = $baseName;

        while ($this->repository->query()->where('name', $name)->exists()) {
            $name = $baseName . ' - ' . $counter;
            $counter++;
        }

        return $name;
    }

    public function validateClassRoomData(array $data): array
    {
        $errors = [];

        // Vérifier si le nom existe déjà (pour création)
        if (isset($data['name'])) {
            $existing = $this->repository->findByName($data['name']);
            if ($existing) {
                $errors[] = "Le nom de classe '{$data['name']}' existe déjà.";
            }
        }

        // Vérifier statut valide
        $validStatuses = ['active', 'inactive', 'archived'];
        if (isset($data['status']) && !in_array($data['status'], $validStatuses)) {
            $errors[] = "Le statut '{$data['status']}' n'est pas valide.";
        }

        // Vérifier que la capacité est positive
        if (isset($data['capacity']) && $data['capacity'] < 0) {
            $errors[] = "La capacité ne peut pas être négative.";
        }

        return $errors;
    }

    public function bulkStatusUpdate(array $classIds, string $status): int
    {
        $validStatuses = ['active', 'inactive', 'archived'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Statut invalide: {$status}");
        }

        $classes = $this->repository->getByIds($classIds);
        $updated = 0;

        foreach ($classes as $class) {
            $class->update(['status' => $status]);
            $updated++;
        }

        return $updated;
    }

    public function canDeleteClass(int $classId): bool
    {
        $classRoom = $this->findClassRoom($classId);

        // Ne peut pas supprimer si des étudiants actifs
        if ($classRoom->current_students_count > 0) {
            return false;
        }

        // Ne peut pas supprimer si assignée à des matières cette année
        if ($classRoom->currentSubjects()->exists()) {
            return false;
        }

        return true;
    }
}
