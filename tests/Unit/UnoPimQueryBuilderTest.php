<?php

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Validation\ValidationException;
use Webkul\MCP\Services\UnoPimQueryBuilder;

beforeEach(function () {
    $this->builder = Mockery::mock(UnoPimQueryBuilder::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $this->builder->shouldReceive('columns')
        ->andReturn([
            'id' => 'bigint',
            'sku' => 'varchar',
            'status' => 'varchar',
            'type' => 'varchar',
            'name' => 'varchar',
            'price' => 'decimal',
            'stock' => 'int',
            'values' => 'json',
            'created_at' => 'timestamp',
        ]);

    $this->query = function (?Model $model = null): Builder {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('getModel')->andReturn($model ?? new class extends Model {});

        return $query;
    };

    $this->translatableModel = new class extends Model
    {
        public array $translatedAttributes = ['label'];

        public function translations(): void {}
    };
});

it('applies equals filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('status', '=', 'active');

    $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
    ]);
});

it('applies not equals filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('status', '!=', 'draft');

    $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '!=', 'value' => 'draft'],
    ]);
});

it('applies IN filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereIn')->once()->with('type', ['simple', 'configurable']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'IN', 'value' => ['simple', 'configurable']],
    ]);
});

it('applies NOT IN filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereNotIn')->once()->with('type', ['bundle']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'NOT IN', 'value' => ['bundle']],
    ]);
});

it('applies CONTAINS filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('name', 'like', '%shirt%');

    $this->builder->applyFilters($query, [
        ['field' => 'name', 'operator' => 'CONTAINS', 'value' => 'shirt'],
    ]);
});

it('applies STARTS WITH filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('sku', 'like', 'PRD%');

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'STARTS WITH', 'value' => 'PRD'],
    ]);
});

it('applies ENDS WITH filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('sku', 'like', '%001');

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'ENDS WITH', 'value' => '001'],
    ]);
});

it('applies greater than filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('price', '>', 100);

    $this->builder->applyFilters($query, [
        ['field' => 'price', 'operator' => '>', 'value' => 100],
    ]);
});

it('applies less than filter correctly', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('stock', '<', 5);

    $this->builder->applyFilters($query, [
        ['field' => 'stock', 'operator' => '<', 'value' => 5],
    ]);
});

it('applies multiple filters sequentially', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('status', '=', 'active');
    $query->shouldReceive('where')->once()->with('name', 'like', '%test%');

    $result = $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
        ['field' => 'name', 'operator' => 'CONTAINS', 'value' => 'test'],
    ]);

    expect($result)->toBe($query);
});

it('reads the column listing once for several filters', function () {
    $builder = Mockery::mock(UnoPimQueryBuilder::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $builder->shouldReceive('columns')->once()->andReturn(['status' => 'varchar', 'sku' => 'varchar']);

    $query = ($this->query)();
    $query->shouldReceive('where')->twice();

    $builder->applyFilters($query, [
        ['field' => 'status', 'value' => 'active'],
        ['field' => 'sku', 'value' => 'A'],
    ]);
});

it('rejects a filter without a field with a localized validation error', function () {
    $query = ($this->query)();

    try {
        $this->builder->applyFilters($query, [
            ['operator' => '=', 'value' => 'test'],
        ]);

        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toBe([
            'filters.0.field' => [trans('mcp::app.tools.filters.missing-field')],
        ]);
    }
});

it('rejects an unsupported operator with a localized validation error', function () {
    $query = ($this->query)();

    try {
        $this->builder->applyFilters($query, [
            ['field' => 'name', 'operator' => 'BETWEEN', 'value' => [1, 10]],
        ]);

        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toBe([
            'filters.0.operator' => [trans('mcp::app.tools.filters.invalid-operator', [
                'operator' => 'BETWEEN',
                'operators' => implode(', ', UnoPimQueryBuilder::OPERATORS),
            ])],
        ]);
    }
});

it('rejects a field that is not a column of the table', function () {
    $query = ($this->query)();
    $query->shouldNotReceive('where');

    expect(fn () => $this->builder->applyFilters($query, [
        ['field' => 'password', 'value' => 'x'],
    ]))->toThrow(ValidationException::class, trans('mcp::app.tools.filters.invalid-field', [
        'field' => 'password',
        'fields' => 'id, sku, status, type, name, price, stock, values, created_at',
    ]));
});

it('reports the index of the failing filter', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('status', '=', 'active');

    try {
        $this->builder->applyFilters($query, [
            ['field' => 'status', 'value' => 'active'],
            ['field' => 'unknown', 'value' => 'x'],
        ]);

        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect(array_keys($e->errors()))->toBe(['filters.1.field']);
    }
});

it('rejects a list value for a single value operator', function () {
    $query = ($this->query)();
    $query->shouldNotReceive('where');

    expect(fn () => $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '=', 'value' => ['a', 'b']],
    ]))->toThrow(ValidationException::class, trans('mcp::app.tools.filters.invalid-value', [
        'field' => 'status',
        'operator' => '=',
    ]));
});

it('allows a JSON path into a real column', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('values->common->color', '=', 'red');

    $this->builder->applyFilters($query, [
        ['field' => 'values->common->color', 'value' => 'red'],
    ]);
});

it('rejects a JSON path segment with unsafe characters', function () {
    $query = ($this->query)();
    $query->shouldNotReceive('where');

    expect(fn () => $this->builder->applyFilters($query, [
        ['field' => "values->common') or 1=1--", 'value' => 'x'],
    ]))->toThrow(ValidationException::class);
});

it('filters a translated attribute through the translations relation', function () {
    $translations = Mockery::mock(Builder::class);
    $translations->shouldReceive('where')->once()->with('label', 'like', '%red%');

    $query = ($this->query)($this->translatableModel);
    $query->shouldReceive('whereHas')->once()
        ->with('translations', Mockery::type(Closure::class))
        ->andReturnUsing(fn (string $relation, Closure $constraint) => $constraint($translations));

    $this->builder->applyFilters($query, [
        ['field' => 'label', 'operator' => 'CONTAINS', 'value' => 'red'],
    ]);
});

it('excludes rows with a matching translation for negated operators', function () {
    $translations = Mockery::mock(Builder::class);
    $translations->shouldReceive('whereIn')->once()->with('label', ['Red', 'Blue']);

    $query = ($this->query)($this->translatableModel);
    $query->shouldNotReceive('whereHas');
    $query->shouldReceive('whereDoesntHave')->once()
        ->with('translations', Mockery::type(Closure::class))
        ->andReturnUsing(fn (string $relation, Closure $constraint) => $constraint($translations));

    $this->builder->applyFilters($query, [
        ['field' => 'label', 'operator' => 'NOT IN', 'value' => 'Red,Blue'],
    ]);
});

it('handles case-insensitive operator matching', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereIn')->once()->with('type', ['simple']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'in', 'value' => ['simple']],
    ]);
});

it('defaults operator to equals when not specified', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('status', '=', 'active');

    $this->builder->applyFilters($query, [
        ['field' => 'status', 'value' => 'active'],
    ]);
});

it('wraps scalar value in array for IN operator', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereIn')->once()->with('id', [42]);

    $this->builder->applyFilters($query, [
        ['field' => 'id', 'operator' => 'IN', 'value' => 42],
    ]);
});

it('returns empty query when no filters are provided', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldNotReceive('getModel');

    $result = $this->builder->applyFilters($query, []);

    expect($result)->toBe($query);
});

it('splits a comma separated string for the IN operator', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereIn')->once()->with('sku', ['SKU-A', 'SKU-B']);

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'IN', 'value' => 'SKU-A, SKU-B'],
    ]);
});

it('splits a comma separated string for the NOT IN operator', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereNotIn')->once()->with('type', ['bundle', 'grouped']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'NOT IN', 'value' => 'bundle,grouped'],
    ]);
});

it('drops empty entries from doubled or trailing commas', function () {
    $query = ($this->query)();
    $query->shouldReceive('whereIn')->once()->with('sku', ['SKU-A', 'SKU-B']);

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'IN', 'value' => 'SKU-A,,SKU-B,'],
    ]);
});

it('keeps commas inside a CONTAINS value', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('name', 'like', '%red, blue%');

    $this->builder->applyFilters($query, [
        ['field' => 'name', 'operator' => 'CONTAINS', 'value' => 'red, blue'],
    ]);
});

it('paginates from the first page when no cursor is given', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('cursorPaginate')->once()->with(10, ['*'], 'cursor', null)
        ->andReturn(Mockery::mock(CursorPaginator::class));

    $this->builder->paginate($query, 10);
});

it('passes a decoded cursor to the paginator', function () {
    $cursor = new Cursor(['id' => 5]);

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('cursorPaginate')->once()
        ->with(10, ['*'], 'cursor', Mockery::on(fn (Cursor $given) => $given->parameter('id') === 5))
        ->andReturn(Mockery::mock(CursorPaginator::class));

    $this->builder->paginate($query, 10, $cursor->encode());
});

it('rejects a cursor that does not decode', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldNotReceive('cursorPaginate');

    expect(fn () => $this->builder->paginate($query, 10, 'not-a-cursor'))
        ->toThrow(ValidationException::class, trans('mcp::app.tools.filters.invalid-cursor'));
});

it('rejects a cursor issued for a differently ordered search', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('cursorPaginate')->once()->andThrow(new UnexpectedValueException('Unable to find parameter [sku] in pagination item.'));

    expect(fn () => $this->builder->paginate($query, 10, (new Cursor(['id' => 5]))->encode()))
        ->toThrow(ValidationException::class, trans('mcp::app.tools.filters.invalid-cursor'));
});

it('rejects a value that is not a date for a date column', function () {
    $query = ($this->query)();
    $query->shouldNotReceive('where');

    try {
        $this->builder->applyFilters($query, [
            ['field' => 'created_at', 'operator' => '>', 'value' => 'not-a-date'],
        ]);

        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toBe([
            'filters.0.value' => [trans('mcp::app.tools.filters.invalid-date', ['field' => 'created_at', 'value' => 'not-a-date'])],
        ]);
    }
});

it('accepts a date or datetime value for a date column', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('created_at', '>', '2026-01-31');
    $query->shouldReceive('where')->once()->with('created_at', '<', '2026-02-01 13:45:00');

    $this->builder->applyFilters($query, [
        ['field' => 'created_at', 'operator' => '>', 'value' => '2026-01-31'],
        ['field' => 'created_at', 'operator' => '<', 'value' => '2026-02-01 13:45:00'],
    ]);
});

it('rejects a value that is not a number for a numeric column', function () {
    $query = ($this->query)();
    $query->shouldNotReceive('where');

    expect(fn () => $this->builder->applyFilters($query, [
        ['field' => 'stock', 'operator' => '>', 'value' => 'many'],
    ]))->toThrow(ValidationException::class, trans('mcp::app.tools.filters.invalid-number', ['field' => 'stock', 'value' => 'many']));
});

it('accepts a numeric string for a numeric column', function () {
    $query = ($this->query)();
    $query->shouldReceive('where')->once()->with('price', '>', '19.99');

    $this->builder->applyFilters($query, [
        ['field' => 'price', 'operator' => '>', 'value' => '19.99'],
    ]);
});

it('checks every entry of a list against the column type', function () {
    $query = ($this->query)();
    $query->shouldNotReceive('whereIn');

    expect(fn () => $this->builder->applyFilters($query, [
        ['field' => 'id', 'operator' => 'IN', 'value' => '1, two'],
    ]))->toThrow(ValidationException::class, trans('mcp::app.tools.filters.invalid-number', ['field' => 'id', 'value' => 'two']));
});
