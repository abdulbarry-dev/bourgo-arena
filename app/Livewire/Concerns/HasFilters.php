<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    /**
     * Apply a text search across multiple columns.
     */
    protected function applySearchFilter(Builder $query, ?string $search, array $columns = []): Builder
    {
        if (! $search) {
            return $query;
        }

        $query->where(function (Builder $q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%'.$search.'%');
            }
        });

        return $query;
    }

    /**
     * Apply a boolean-related filter where a relation exists (has sessions).
     * $filter may be 'all', 'with', 'without'.
     */
    protected function applyRelationPresenceFilter(Builder $query, string $relation, ?string $filter): Builder
    {
        if ($filter === 'with') {
            return $query->whereHas($relation);
        }

        if ($filter === 'without') {
            return $query->whereDoesntHave($relation);
        }

        return $query;
    }
}
