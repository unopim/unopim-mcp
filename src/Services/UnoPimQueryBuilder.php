<?php

namespace Webkul\MCP\Services;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

class UnoPimQueryBuilder
{
    /**
     * The supported filter operators.
     */
    public const OPERATORS = ['=', '!=', 'IN', 'NOT IN', 'CONTAINS', 'STARTS WITH', 'ENDS WITH', '>', '<'];

    /**
     * Map of operators to their Eloquent equivalents or handlers.
     */
    protected array $operatorMap = [
        '=' => 'where',
        '!=' => 'where',
        'IN' => 'whereIn',
        'NOT IN' => 'whereNotIn',
        'CONTAINS' => 'like',
        'STARTS WITH' => 'like_start',
        'ENDS WITH' => 'like_end',
        '>' => 'where',
        '<' => 'where',
    ];

    /**
     * Apply filters to the query builder.
     *
     * Fields are checked against the table's real columns (plus JSON paths into them and the
     * model's translated attributes), and values against date and numeric column types, before
     * they reach the query, so a bad filter is a validation error rather than a database
     * exception or a silently empty result.
     *
     * @throws ValidationException
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        $columns = null;

        foreach (array_values($filters) as $index => $filter) {
            $this->applyFilter($query, is_array($filter) ? $filter : [], $index, $columns ??= $this->columns($query->getModel()));
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     *
     * @param  array<string, string>  $columns
     *
     * @throws ValidationException
     */
    protected function applyFilter(Builder $query, array $filter, int $index, array $columns): void
    {
        $field = $filter['field'] ?? null;
        $operator = strtoupper(trim((string) ($filter['operator'] ?? '=')));
        $value = $filter['value'] ?? null;

        if (! is_string($field) || trim($field) === '') {
            $this->fail("filters.{$index}.field", 'mcp::app.tools.filters.missing-field');
        }

        if (! isset($this->operatorMap[$operator])) {
            $this->fail("filters.{$index}.operator", 'mcp::app.tools.filters.invalid-operator', [
                'operator' => $operator,
                'operators' => implode(', ', self::OPERATORS),
            ]);
        }

        if (! in_array($operator, ['IN', 'NOT IN'], true) && is_array($value)) {
            $this->fail("filters.{$index}.value", 'mcp::app.tools.filters.invalid-value', [
                'field' => $field,
                'operator' => $operator,
            ]);
        }

        $translated = $this->translatedAttributes($query->getModel());

        if (in_array($field, $translated, true)) {
            $this->applyTranslationCondition($query, $field, $operator, $value);

            return;
        }

        if (! $this->isFilterableField($field, array_keys($columns))) {
            $this->fail("filters.{$index}.field", 'mcp::app.tools.filters.invalid-field', [
                'field' => $field,
                'fields' => implode(', ', [...array_keys($columns), ...$translated]),
            ]);
        }

        if (isset($columns[$field]) && ! str_contains($this->operatorMap[$operator], 'like')) {
            $this->assertValueMatchesType($field, $columns[$field], $operator, $value, $index);
        }

        $this->applyCondition($query, $field, $operator, $value);
    }

    /**
     * Reject a comparison value that a date or numeric column can never match.
     *
     * @throws ValidationException
     */
    protected function assertValueMatchesType(string $field, string $type, string $operator, mixed $value, int $index): void
    {
        $type = strtolower($type);

        $kind = match (true) {
            in_array($type, ['date', 'datetime', 'timestamp', 'timestamptz'], true),
            str_starts_with($type, 'timestamp') => 'date',
            in_array($type, ['int', 'integer', 'bigint', 'smallint', 'mediumint', 'int2', 'int4', 'int8', 'decimal', 'numeric', 'float', 'float4', 'float8', 'double', 'double precision', 'real'], true) => 'number',
            default => null,
        };

        if ($kind === null) {
            return;
        }

        $values = in_array($operator, ['IN', 'NOT IN'], true) ? $this->listValue($value) : [$value];

        foreach ($values as $item) {
            if ($item === null || $this->matchesKind($kind, $item)) {
                continue;
            }

            $this->fail("filters.{$index}.value", "mcp::app.tools.filters.invalid-{$kind}", [
                'field' => $field,
                'value' => is_scalar($item) ? (string) $item : json_encode($item),
            ]);
        }
    }

    /**
     * Determine whether a filter value is a date or a number, as the column requires.
     */
    protected function matchesKind(string $kind, mixed $value): bool
    {
        if ($kind === 'number') {
            return is_int($value) || is_float($value) || is_bool($value) || is_numeric($value);
        }

        return is_string($value) && trim($value) !== '' && strtotime($value) !== false;
    }

    /**
     * Apply one comparison to the query.
     */
    protected function applyCondition(Builder $query, string $field, string $operator, mixed $value): void
    {
        switch ($this->operatorMap[$operator]) {
            case 'where':
                $query->where($field, $operator, $value);
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
     * Filter on a translated attribute through the model's translations relation.
     *
     * Negative operators exclude rows that have any matching translation, rather than
     * matching rows that merely have one non-matching translation.
     */
    protected function applyTranslationCondition(Builder $query, string $field, string $operator, mixed $value): void
    {
        $negated = ['!=' => '=', 'NOT IN' => 'IN'][$operator] ?? null;

        $constraint = fn (Builder $translations): mixed => $this->applyCondition($translations, $field, $negated ?? $operator, $value);

        $negated
            ? $query->whereDoesntHave('translations', $constraint)
            : $query->whereHas('translations', $constraint);
    }

    /**
     * Determine whether a field is a real column, or a JSON path into one.
     *
     * @param  array<int, string>  $columns
     */
    protected function isFilterableField(string $field, array $columns): bool
    {
        $segments = explode('->', $field);

        if (! in_array(array_shift($segments), $columns, true)) {
            return false;
        }

        foreach ($segments as $segment) {
            if (! preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The model table's column names mapped to their database type names, read through the
     * schema builder so the table prefix applies.
     *
     * @return array<string, string>
     */
    protected function columns(Model $model): array
    {
        return collect($model->getConnection()->getSchemaBuilder()->getColumns($model->getTable()))
            ->mapWithKeys(fn (array $column): array => [$column['name'] => (string) ($column['type_name'] ?? '')])
            ->all();
    }

    /**
     * The model's translated attribute names, when it is translatable.
     *
     * @return array<int, string>
     */
    protected function translatedAttributes(Model $model): array
    {
        if (! method_exists($model, 'translations') || ! property_exists($model, 'translatedAttributes')) {
            return [];
        }

        return array_values((array) $model->translatedAttributes);
    }

    /**
     * Normalize an IN / NOT IN value into a list.
     *
     * Clients that cannot send arrays pass multiple values as "a,b"; arrays are used as given.
     *
     * @return array<int, mixed>
     */
    protected function listValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return collect((array) $value)
            ->map(fn (mixed $item): mixed => is_string($item) ? trim($item) : $item)
            ->reject(fn (mixed $item): bool => $item === '' || $item === null)
            ->values()
            ->all();
    }

    /**
     * Apply cursor pagination to the query.
     *
     * The cursor is passed explicitly rather than through the request, and one that does not
     * decode, or was issued for a differently ordered search, is rejected instead of silently
     * restarting at page one.
     *
     * @throws ValidationException
     */
    public function paginate(Builder $query, int $limit = 25, ?string $cursor = null): CursorPaginator
    {
        $decoded = null;

        if ($cursor !== null && $cursor !== '') {
            $decoded = Cursor::fromEncoded($cursor) ?? $this->fail('cursor', 'mcp::app.tools.filters.invalid-cursor');
        }

        try {
            return $query->cursorPaginate($limit, ['*'], 'cursor', $decoded);
        } catch (UnexpectedValueException) {
            $this->fail('cursor', 'mcp::app.tools.filters.invalid-cursor');
        }
    }

    /**
     * Throw a validation error for one input key.
     *
     * @throws ValidationException
     */
    protected function fail(string $key, string $message, array $replace = []): never
    {
        throw ValidationException::withMessages([$key => trans($message, $replace)]);
    }
}
