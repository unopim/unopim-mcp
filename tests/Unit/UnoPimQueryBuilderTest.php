<?php

use Illuminate\Database\Eloquent\Builder;
use Webkul\MCP\Services\UnoPimQueryBuilder;

beforeEach(function () {
    $this->builder = new UnoPimQueryBuilder;
});

it('applies equals filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('status', '=', 'active');

    $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
    ]);
});

it('applies not equals filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('status', '!=', 'draft');

    $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '!=', 'value' => 'draft'],
    ]);
});

it('applies IN filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereIn')->once()->with('type', ['simple', 'configurable']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'IN', 'value' => ['simple', 'configurable']],
    ]);
});

it('applies NOT IN filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereNotIn')->once()->with('type', ['bundle']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'NOT IN', 'value' => ['bundle']],
    ]);
});

it('applies CONTAINS filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('name', 'like', '%shirt%');

    $this->builder->applyFilters($query, [
        ['field' => 'name', 'operator' => 'CONTAINS', 'value' => 'shirt'],
    ]);
});

it('applies STARTS WITH filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('sku', 'like', 'PRD%');

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'STARTS WITH', 'value' => 'PRD'],
    ]);
});

it('applies ENDS WITH filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('sku', 'like', '%001');

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'ENDS WITH', 'value' => '001'],
    ]);
});

it('applies greater than filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('price', '>', 100);

    $this->builder->applyFilters($query, [
        ['field' => 'price', 'operator' => '>', 'value' => 100],
    ]);
});

it('applies less than filter correctly', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('stock', '<', 5);

    $this->builder->applyFilters($query, [
        ['field' => 'stock', 'operator' => '<', 'value' => 5],
    ]);
});

it('applies multiple filters sequentially', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('status', '=', 'active');
    $query->shouldReceive('where')->once()->with('name', 'like', '%test%');

    $result = $this->builder->applyFilters($query, [
        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
        ['field' => 'name', 'operator' => 'CONTAINS', 'value' => 'test'],
    ]);

    expect($result)->toBe($query);
});

it('throws exception for missing field', function () {
    $query = Mockery::mock(Builder::class);

    expect(fn () => $this->builder->applyFilters($query, [
        ['operator' => '=', 'value' => 'test'],
    ]))->toThrow(InvalidArgumentException::class, "Filter 'field' is required.");
});

it('throws exception for unsupported operator', function () {
    $query = Mockery::mock(Builder::class);

    expect(fn () => $this->builder->applyFilters($query, [
        ['field' => 'name', 'operator' => 'BETWEEN', 'value' => [1, 10]],
    ]))->toThrow(InvalidArgumentException::class, "Operator 'BETWEEN' is not supported.");
});

it('handles case-insensitive operator matching', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereIn')->once()->with('type', ['simple']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'in', 'value' => ['simple']],
    ]);
});

it('defaults operator to equals when not specified', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('status', '=', 'active');

    $this->builder->applyFilters($query, [
        ['field' => 'status', 'value' => 'active'],
    ]);
});

it('wraps scalar value in array for IN operator', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereIn')->once()->with('id', [42]);

    $this->builder->applyFilters($query, [
        ['field' => 'id', 'operator' => 'IN', 'value' => 42],
    ]);
});

it('returns empty query when no filters are provided', function () {
    $query = Mockery::mock(Builder::class);

    $result = $this->builder->applyFilters($query, []);

    expect($result)->toBe($query);
});

it('splits a comma separated string for the IN operator', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereIn')->once()->with('sku', ['SKU-A', 'SKU-B']);

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'IN', 'value' => 'SKU-A, SKU-B'],
    ]);
});

it('splits a comma separated string for the NOT IN operator', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereNotIn')->once()->with('type', ['bundle', 'grouped']);

    $this->builder->applyFilters($query, [
        ['field' => 'type', 'operator' => 'NOT IN', 'value' => 'bundle,grouped'],
    ]);
});

it('drops empty entries from doubled or trailing commas', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('whereIn')->once()->with('sku', ['SKU-A', 'SKU-B']);

    $this->builder->applyFilters($query, [
        ['field' => 'sku', 'operator' => 'IN', 'value' => 'SKU-A,,SKU-B,'],
    ]);
});

it('keeps commas inside a CONTAINS value', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')->once()->with('name', 'like', '%red, blue%');

    $this->builder->applyFilters($query, [
        ['field' => 'name', 'operator' => 'CONTAINS', 'value' => 'red, blue'],
    ]);
});
