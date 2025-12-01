<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearch(Builder $query, ?string $search, array $columns = [])
    {
        if (empty($search)) {
            return $query;
        }

        $searchColumns = !empty($columns) ? $columns : $this->searchable ?? [];

        return $query->where(function ($q) use ($search, $searchColumns) {
            foreach ($searchColumns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }
}
