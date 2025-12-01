<?php

namespace Modules\Academic\Repositories;

use Modules\Academic\Entities\ClassRoom;
use Illuminate\Database\Eloquent\Collection;

class ClassRoomRepository
{
    public function __construct(
        private ClassRoom $model
    ) {}

    public function query()
    {
        return $this->model->query();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?ClassRoom
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): ClassRoom
    {
        return $this->model->findOrFail($id);
    }

    public function findByName(string $name): ?ClassRoom
    {
        return $this->model->where('name', $name)->first();
    }

    public function create(array $data): ClassRoom
    {
        return $this->model->create($data);
    }

    public function update(ClassRoom $classRoom, array $data): bool
    {
        return $classRoom->update($data);
    }

    public function delete(ClassRoom $classRoom): bool
    {
        return $classRoom->delete();
    }

    public function getByLevel(string $level): Collection
    {
        return $this->model->byLevel($level)->get();
    }

    public function getByStream(string $stream): Collection
    {
        return $this->model->byStream($stream)->get();
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

    public function countByLevel(): array
    {
        return $this->model->selectRaw('level, COUNT(*) as count')
                          ->groupBy('level')
                          ->pluck('count', 'level')
                          ->toArray();
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function getByLevelGrouped()
    {
        return $this->model->active()
                          ->orderByLevel()
                          ->get()
                          ->groupBy(['level', 'stream']);
    }
}
