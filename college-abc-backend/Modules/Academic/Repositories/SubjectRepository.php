<?php

namespace Modules\Academic\Repositories;

use Modules\Academic\Entities\Subject;
use Illuminate\Database\Eloquent\Collection;

class SubjectRepository
{
    public function __construct(
        private Subject $model
    ) {}

    public function query()
    {
        return $this->model->query();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Subject
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Subject
    {
        return $this->model->findOrFail($id);
    }

    public function findByCode(string $code): ?Subject
    {
        return $this->model->where('code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Subject
    {
        return $this->model->where('code', $code)->firstOrFail();
    }

    public function create(array $data): Subject
    {
        return $this->model->create($data);
    }

    public function update(Subject $subject, array $data): bool
    {
        return $subject->update($data);
    }

    public function delete(Subject $subject): bool
    {
        return $subject->delete();
    }

    public function getByCategory(string $category): Collection
    {
        return $this->model->byCategory($category)->get();
    }

    public function getByLevelType(string $levelType): Collection
    {
        return $this->model->byLevelType($levelType)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function search(string $query): Collection
    {
        return $this->model->search($query)->get();
    }

    public function paginate(int $perPage = 15, array $relations = [])
    {
        return $this->model->with($relations)->paginate($perPage);
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function countByCategory(): array
    {
        return $this->model->selectRaw('category, COUNT(*) as count')
                          ->groupBy('category')
                          ->pluck('count', 'category')
                          ->toArray();
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function bulkUpdate(array $subjects, array $data): int
    {
        return $this->model->whereIn('id', $subjects)->update($data);
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }
}
