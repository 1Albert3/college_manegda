<?php

namespace Modules\Academic\Repositories;

use Modules\Academic\Entities\AcademicYear;
use Illuminate\Database\Eloquent\Collection;

class AcademicYearRepository
{
    public function __construct(
        private AcademicYear $model
    ) {}

    public function query()
    {
        return $this->model->query();
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?AcademicYear
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): AcademicYear
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): AcademicYear
    {
        return $this->model->create($data);
    }

    public function update(AcademicYear $year, array $data): bool
    {
        return $year->update($data);
    }

    public function delete(AcademicYear $year): bool
    {
        return $year->delete();
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    public function getCurrent(): ?AcademicYear
    {
        return $this->model->current()->first();
    }

    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    public function getOngoing(): ?AcademicYear
    {
        return $this->model->ongoing()->first();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }
}
