<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

trait PaginatesQueries
{
    protected function perPage(Request $request, int $default = 25, int $max = 100): int
    {
        return min($max, max(10, (int) $request->input('per_page', $default)));
    }

    /** @param Builder|EloquentBuilder $query */
    protected function applySearch($query, Request $request, array $columns): void
    {
        $search = trim((string) $request->input('search', ''));
        if ($search === '' || $columns === []) {
            return;
        }

        $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'ILIKE', '%' . $search . '%');
            }
        });
    }

    /** @param Builder|EloquentBuilder $query */
    protected function paginateQuery($query, Request $request, array $searchColumns = [], int $defaultPerPage = 25)
    {
        $this->applySearch($query, $request, $searchColumns);

        return response()->json($query->paginate($this->perPage($request, $defaultPerPage)));
    }
}
