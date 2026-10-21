<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\AttributeSearchTool;
use Webkul\MCP\Tools\Catalog\CatalogSchemaTool;
use Webkul\MCP\Tools\Catalog\CategorySearchTool;
use Webkul\MCP\Tools\Catalog\ProductSearchTool;
use Webkul\MCP\Tools\DataTransfer\JobSearchTool;
use Webkul\MCP\Tools\Settings\CurrencySearchTool;
use Webkul\MCP\Tools\Settings\SettingSearchTool;
use Webkul\Product\Models\Product;

it('fetches catalog schema through the MCP tool', function () {
    UnoPimAgentServer::tool(CatalogSchemaTool::class)
        ->assertOk()
        ->assertSee('filterable_fields')
        ->assertSee('operators');
});

it('searches attributes through the MCP tool', function () {
    Attribute::factory()->count(2)->create();

    UnoPimAgentServer::tool(AttributeSearchTool::class, ['limit' => 2])
        ->assertOk()
        ->assertSee('attributes')
        ->assertSee('code');
});

it('searches categories through the MCP tool', function () {
    $category = Category::factory()->create();

    UnoPimAgentServer::tool(CategorySearchTool::class, ['limit' => 5])
        ->assertOk()
        ->assertSee('categories')
        ->assertSee($category->code);
});

it('searches products through the MCP tool', function () {
    $product = Product::factory()->create(['sku' => 'TEST-MCP-'.uniqid()]);

    UnoPimAgentServer::tool(ProductSearchTool::class, ['limit' => 10])
        ->assertOk()
        ->assertSee('products')
        ->assertSee($product->sku);
});

it('returns a clean error for an unknown filter operator, field or column', function (array $filter, string $expected) {
    UnoPimAgentServer::tool(ProductSearchTool::class, ['filters' => [$filter]])
        ->assertHasErrors([$expected])
        ->assertDontSee('unexpected error');
})->with([
    'unknown operator' => [['field' => 'sku', 'operator' => 'LIKE', 'value' => 'x'], 'LIKE'],
    'missing field' => [['operator' => '=', 'value' => 'x'], 'field'],
    'unknown column' => [['field' => 'no_such_column', 'operator' => '=', 'value' => 'x'], 'no_such_column'],
    'injected column' => [['field' => 'sku) or (1=1', 'operator' => '=', 'value' => 'x'], 'sku) or (1=1'],
    'malformed json path' => [['field' => "values->common->sku'", 'operator' => '=', 'value' => 'x'], 'values->common->sku'],
]);

it('returns a clean error for an invalid cursor', function () {
    UnoPimAgentServer::tool(ProductSearchTool::class, ['cursor' => 'not-a-real-cursor'])
        ->assertHasErrors()
        ->assertDontSee('unexpected error');

    $foreignCursor = base64_encode(json_encode(['code' => 'x', '_pointsToNextItems' => true]));

    UnoPimAgentServer::tool(ProductSearchTool::class, ['cursor' => rtrim(strtr($foreignCursor, '+/', '-_'), '=')])
        ->assertHasErrors()
        ->assertDontSee('unexpected error');
});

it('advertises only product filter fields that search_products accepts', function () {
    $schema = $this->toolPayload(CatalogSchemaTool::class);
    $channel = core()->getDefaultChannel();

    $placeholders = [
        '{channel}' => $channel->code,
        '{locale}' => $channel->locales->first()->code,
        '{currency}' => $channel->currencies->first()?->code ?? 'USD',
    ];

    expect($schema['filterable_fields']['products'])->not->toBeEmpty();

    foreach ($schema['filterable_fields']['products'] as $entry) {
        $field = strtr($entry['field'], $placeholders);

        $value = match ($entry['type']) {
            'integer' => 0,
            'boolean' => true,
            'datetime' => '2020-01-01 00:00:00',
            default => 'x',
        };

        UnoPimAgentServer::tool(ProductSearchTool::class, [
            'filters' => [['field' => $field, 'operator' => '=', 'value' => $value]],
        ])->assertHasNoErrors();
    }

    expect(collect($schema['filterable_fields']['products'])->pluck('field'))->not->toContain('name', 'price');
});

it('filters on a product json value path', function () {
    $sku = 'TEST-JSON-'.uniqid();
    $product = Product::factory()->create(['sku' => $sku, 'values' => ['common' => ['sku' => $sku]]]);

    UnoPimAgentServer::tool(ProductSearchTool::class, [
        'filters' => [['field' => 'values->common->sku', 'operator' => '=', 'value' => $product->sku]],
    ])->assertOk()->assertSee($product->sku);
});

it('declares filter values as a string, number or list', function (string $tool) {
    $value = app($tool)->toArray()['inputSchema']['properties']['filters']['items']['properties']['value'];

    $types = collect($value['anyOf'] ?? [])->pluck('type')->all();

    expect($types)->toContain('string', 'number', 'array');
})->with([
    ProductSearchTool::class,
    CategorySearchTool::class,
    AttributeSearchTool::class,
    SettingSearchTool::class,
    CurrencySearchTool::class,
    JobSearchTool::class,
]);

it('rejects a filter value that does not match a date or numeric column type', function () {
    Product::factory()->simple()->create();

    UnoPimAgentServer::tool(ProductSearchTool::class, [
        'filters' => [['field' => 'created_at', 'operator' => '>', 'value' => 'not-a-date']],
    ])->assertHasErrors([trans('mcp::app.tools.filters.invalid-date', ['field' => 'created_at', 'value' => 'not-a-date'])]);

    UnoPimAgentServer::tool(ProductSearchTool::class, [
        'filters' => [['field' => 'id', 'operator' => '>', 'value' => 'abc']],
    ])->assertHasErrors([trans('mcp::app.tools.filters.invalid-number', ['field' => 'id', 'value' => 'abc'])]);

    UnoPimAgentServer::tool(ProductSearchTool::class, [
        'filters' => [['field' => 'created_at', 'operator' => '>', 'value' => '2000-01-01']],
    ])->assertOk();
});
