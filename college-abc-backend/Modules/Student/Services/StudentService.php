<?php

namespace Modules\Student\Services;

use Modules\Student\Entities\Student;
use Modules\Student\Entities\Enrollment;
use Modules\Student\Repositories\StudentRepository;
use Modules\Core\Entities\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

class StudentService
{
    public function __construct(
        private StudentRepository $repository
    ) {}

    public function createStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            // Créer le user associé
            $userData = [
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'] ?? $this->generateEmail($data),
                'password' => $data['password'] ?? 'password123', // À changer en prod
                'phone' => $data['phone'] ?? null,
                'role_type' => 'student',
                'is_active' => $data['is_active'] ?? true,
            ];

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'phone' => $userData['phone'],
                'role_type' => $userData['role_type'],
                'is_active' => $userData['is_active'],
            ]);

            $user->assignRole('student');

            // Préparer les données student
            $studentData = [
                'user_id' => $user->id,
                'matricule' => $data['matricule'] ?? $this->generateMatricule(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'place_of_birth' => $data['place_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'photo' => $data['photo'] ?? null,
                'status' => $data['status'] ?? 'active',
                'medical_info' => $data['medical_info'] ?? null,
            ];

            $student = $this->repository->create($studentData);

            // Attacher les parents si fournis
            if (isset($data['parents'])) {
                foreach ($data['parents'] as $parentData) {
                    $this->attachParent(
                        $student->id,
                        $parentData['parent_id'] ?? $parentData['id'],
                        $parentData['relationship'],
                        $parentData['is_primary'] ?? false
                    );
                }
            }

            return $student->fresh(['user', 'parents']);
        });
    }

    public function updateStudent(int $id, array $data): Student
    {
        return DB::transaction(function () use ($id, $data) {
            $student = $this->findStudent($id);

            // Mise à jour des données student
            $studentData = [
                'first_name' => $data['first_name'] ?? $student->first_name,
                'last_name' => $data['last_name'] ?? $student->last_name,
                'date_of_birth' => $data['date_of_birth'] ?? $student->date_of_birth,
                'gender' => $data['gender'] ?? $student->gender,
                'place_of_birth' => $data['place_of_birth'] ?? $student->place_of_birth,
                'address' => $data['address'] ?? $student->address,
                'photo' => $data['photo'] ?? $student->photo,
                'status' => $data['status'] ?? $student->status,
                'medical_info' => $data['medical_info'] ?? $student->medical_info,
            ];

            $student->update($studentData);

            // Mise à jour du user associé
            if (isset($data['email']) || isset($data['phone'])) {
                $userData = [];
                if (isset($data['email'])) $userData['email'] = $data['email'];
                if (isset($data['phone'])) $userData['phone'] = $data['phone'];

                $student->user->update($userData);
            }

            return $student->fresh(['user', 'parents', 'currentEnrollment.class']);
        });
    }

    public function deleteStudent(int $id): bool
    {
        $student = $this->findStudent($id);

        // Supprimer les inscriptions
        $student->enrollments()->delete();

        // Détacher les parents
        $student->parents()->detach();

        // Supprimer l'utilisateur associé
        $student->user->delete();

        return $student->delete();
    }

    public function findStudent(int $id): Student
    {
        return $this->repository->findOrFail($id);
    }

    public function findByMatricule(string $matricule): Student
    {
        return $this->repository->findByMatricule($matricule);
    }

    public function getStudents(array $filters = [], array $relations = [], bool $paginate = true): \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection
    {
        $query = $this->repository->query();

        // Appliquer les filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['class_id'])) {
            $query->byClass($filters['class_id']);
        }

        // Relations par défaut
        $defaultRelations = ['user', 'currentEnrollment.class'];
        $relations = array_unique(array_merge($defaultRelations, $relations));

        $query = $query->with($relations)->orderByName();

        return $paginate
            ? $query->paginate($filters['per_page'] ?? 15)
            : $query->get();
    }

    public function attachParent(int $studentId, int $parentId, string $relationship, bool $isPrimary = false): void
    {
        $student = $this->findStudent($studentId);

        $student->attachParent($parentId, $relationship, $isPrimary);
    }

    public function detachParent(int $studentId, int $parentId): void
    {
        $student = $this->findStudent($studentId);

        $student->detachParent($parentId);
    }

    public function enrollStudent(int $studentId, array $enrollmentData): Enrollment
    {
        $student = $this->findStudent($studentId);

        return Enrollment::create([
            'student_id' => $studentId,
            'academic_year_id' => $enrollmentData['academic_year_id'],
            'class_id' => $enrollmentData['class_id'],
            'enrollment_date' => $enrollmentData['enrollment_date'] ?? now()->toDateString(),
            'status' => $enrollmentData['status'] ?? 'active',
            'discount_percentage' => $enrollmentData['discount_percentage'] ?? 0,
            'notes' => $enrollmentData['notes'] ?? null,
        ]);
    }

    public function updateEnrollment(int $enrollmentId, array $data): Enrollment
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        $enrollment->update([
            'class_id' => $data['class_id'] ?? $enrollment->class_id,
            'status' => $data['status'] ?? $enrollment->status,
            'discount_percentage' => $data['discount_percentage'] ?? $enrollment->discount_percentage,
            'notes' => $data['notes'] ?? $enrollment->notes,
        ]);

        return $enrollment->fresh();
    }

    public function getStudentsByClass(int $classId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->getStudents(array_merge($filters, ['class_id' => $classId]));
    }

    public function getStudentsStats(): array
    {
        $total = Student::count();
        $active = Student::active()->count();
        $inactive = $total - $active;

        $byGender = Student::selectRaw('gender, COUNT(*) as count')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();

        $byStatus = Student::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_gender' => $byGender,
            'by_status' => $byStatus,
        ];
    }

    public function exportStudents(array $filters = []): Collection
    {
        $students = $this->getStudents($filters, ['parents', 'currentEnrollment.class'], false);

        return $students->map(function ($student) {
            return [
                'matricule' => $student->matricule,
                'nom' => $student->full_name,
                'date_naissance' => $student->date_of_birth->format('d/m/Y'),
                'genre' => $student->gender,
                'classe' => $student->current_class_name,
                'status' => $student->status,
                'email' => $student->user->email,
                'telephone' => $student->user->phone,
                'parents' => $student->parents->pluck('name')->join(', '),
                'date_creation' => $student->created_at->format('d/m/Y'),
            ];
        });
    }

    private function generateMatricule(): string
    {
        $year = date('Y');
        $count = Student::whereYear('created_at', $year)->count() + 1;

        return sprintf('STU%s%04d', $year, $count);
    }

    private function generateEmail(array $data): string
    {
        $baseName = strtolower($data['first_name'] . '.' . $data['last_name']);
        $email = $baseName . '@college-abc.com';
        $counter = 1;

        // Vérifier si l'email existe déjà
        while (\Modules\Core\Entities\User::where('email', $email)->exists()) {
            $email = $baseName . $counter . '@college-abc.com';
            $counter++;
        }

        return $email;
    }
}
