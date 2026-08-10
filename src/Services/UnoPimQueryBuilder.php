<?php

namespace Webkul\MCP\Services;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class UnoPimQueryBuilder
{
    /**
     * Map of operators to their Eloquent equivalents or handlers.
     */
    protected array $operatorMap = [
        '='           => 'where',
        '!='          => 'where',
        'IN'          => 'whereIn',
        'NOT IN'      => 'whereNotIn',
        'CONTAINS'    => 'like',
        'STARTS WITH' => 'like_start',
        'ENDS WITH'   => 'like_end',
        '>'           => 'where',
        '<'           => 'where',
    ];

    /**
     * Apply filters to the query builder.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     */
    protected function applyFilter(Builder $query, array $filter): void
    {
        $field = $filter['field'] ?? null;
        $operator = strtoupper($filter['operator'] ?? '=');
        $value = $filter['value'] ?? null;

        if (! $field) {
            throw new InvalidArgumentException("Filter 'field' is required.");
        }

        if (! isset($this->operatorMap[$operator])) {
            throw new InvalidArgumentException("Operator '{$operator}' is not supported.");
        }

        $handler = $this->operatorMap[$operator];

        switch ($handler) {
            case 'where':
                $query->where($field, $operator === '=' ? '=' : $operator, $value);
                break;

            case 'whereIn':
                $query->whereIn($field, $this->listValue($value));
                break;

            case 'whereNotIn':
                $query->whereNotIn($field, $this->listValue($value));
                break;

            case 'like':
                $query->where($field, 'like', '%'.$value.'%');
                break;

            case 'like_start':
                $query->where($field, 'like', $value.'%');
                break;

            case 'like_end':
                $query->where($field, 'like', '%'.$value);
                break;
        }
    }

    /**
     * Normalise the value of an IN / NOT IN filter into a list.
     *
     * The tool schema declares `value` as a string, so a client generating
     * calls from the schema sends "a,b" for multiple values. Casting that
     * straight to an array produced a single element that matched nothing, and
     * returned an empty result set rather than an error, which reads as "these
     * records do not exist". Arrays keep working for clients that send them.
     *
     * @return array<int, mixed>
     */
    protected function listValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return collect((array) $value)
            ->map(fn ($item) => is_string($item) ? trim($item) : $item)
            ->reject(fn ($item): bool => $item === '' || $item === null)
            ->values()
            ->all();
    }

    /**
     * Apply pagination to the query.
     *
     * @return CursorPaginator
     */
    public function paginate(Builder $query, int $limit = 25, ?string $cursor = null)
    {
        if ($cursor) {
            request()->merge(['cursor' => $cursor]);
        }

        return $query->cursorPaginate($limit);
    }
}
