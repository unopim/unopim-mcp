<?php

use Webkul\MCP\Services\UnoPimQueryBuilder;

/**
 * listValue() is protected, so reach it the way the operator handlers do.
 */
function listValue(mixed $value): array
{
    $builder = app(UnoPimQueryBuilder::class);

    $method = (new ReflectionClass($builder))->getMethod('listValue');
    $method->setAccessible(true);

    return $method->invoke($builder, $value);
}

it('splits a comma separated string, which is the form the schema declares', function () {
    expect(listValue('SKU-A,SKU-B'))->toBe(['SKU-A', 'SKU-B']);
});

it('keeps a single value working', function () {
    expect(listValue('SKU-A'))->toBe(['SKU-A']);
});

it('leaves an array untouched', function () {
    expect(listValue(['SKU-A', 'SKU-B']))->toBe(['SKU-A', 'SKU-B']);
});

it('trims whitespace around comma separated entries', function () {
    expect(listValue('SKU-A , SKU-B'))->toBe(['SKU-A', 'SKU-B']);
});

it('drops empty entries from a trailing or doubled comma', function () {
    expect(listValue('SKU-A,,SKU-B,'))->toBe(['SKU-A', 'SKU-B']);
});

it('preserves non string values inside an array', function () {
    expect(listValue([1, 2, 3]))->toBe([1, 2, 3]);
});

it('returns an empty list for an empty value', function () {
    expect(listValue(''))->toBe([])
        ->and(listValue([]))->toBe([]);
});
