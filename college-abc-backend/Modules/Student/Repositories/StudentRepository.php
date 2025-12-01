<?php

namespace Modules\Student\Repositories;

use Modules\Student\Entities\Student;
use Illuminate\Database\Eloquent\Collection;

class StudentRepository
{
    public function __construct(
        private Student $model
    ) {}

    public function query()
    {
        return $this->model->query();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Student
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Student
    {
        return $this->model->findOrFail($id);
    }

    public function findByMatricule(string $matricule): Student
    {
        return $this->model->where('matricule', $matricule)->firstOrFail();
    }

    public function create(array $data): Student
    {
        return $this->model->create($data);
    }

    public function update(Student $student, array $data): bool
    {
        return $student->update($data);
    }

    public function delete(Student $student): bool
    {
        return $student->delete();
    }

    public function getWithRelations(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }

    public function paginate(int $perPage = 15, array $relations = [])
    {
        return $this->model->with($relations)->paginate($perPage);
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByGender(string $gender): Collection
    {
        return $this->model->where('gender', $gender)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function countByStatus(): array
    {
        return $this->model->selectRaw('status, COUNT(*) as count')
                          ->groupBy('status')
                          ->pluck('count', 'status')
                          ->toArray();
    }

    public function search(string $query): Collection
    {
        return $this->model->search($query)->get();
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }
}
