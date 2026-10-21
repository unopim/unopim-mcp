<?php

namespace Webkul\MCP\Tests\Feature;

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\AttributeOptionSearchTool;

it('searches attribute options through the mcp tool', function () {
    // 1. Create a select attribute
    $attribute = Attribute::factory()->create([
        'code' => 'test_select_'.uniqid(),
        'type' => 'select',
    ]);

    // 2. Create options
    $option1 = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'code' => 'opt1',
        'sort_order' => 1,
    ]);

    $option2 = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'code' => 'opt2',
        'sort_order' => 2,
    ]);

    // 3. Search via MCP
    UnoPimAgentServer::tool(AttributeOptionSearchTool::class, [
        'filters' => [
            ['field' => 'attribute_id', 'operator' => '=', 'value' => $attribute->id],
        ],
    ])->assertOk()->assertSee($attribute->id)->assertSee('opt1')->assertSee('opt2');
});

it('pages through options with equal sort orders without losing rows', function () {
    $attribute = Attribute::factory()->create(['code' => 'test_page_'.uniqid(), 'type' => 'select']);

    $codes = collect(range(1, 5))->map(fn (int $i): string => 'same_'.$i);

    foreach ($codes as $code) {
        AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => $code, 'sort_order' => 1]);
    }

    $seen = [];
    $cursor = null;

    do {
        $page = $this->toolPayload(AttributeOptionSearchTool::class, array_filter([
            'filters' => [['field' => 'attribute_id', 'operator' => '=', 'value' => $attribute->id]],
            'limit' => 2,
            'cursor' => $cursor,
        ]));

        $seen = [...$seen, ...collect($page['options'] ?? [])->pluck('code')->all()];
        $cursor = $page['next_cursor'] ?? null;
    } while ($cursor && count($seen) < 20);

    $expected = AttributeOption::where('attribute_id', $attribute->id)->pluck('code')->sort()->values()->all();

    expect(collect($seen)->sort()->values()->all())->toBe($expected)
        ->and($expected)->toContain(...$codes->all());
});

it('filters options by their translated label', function () {
    $attribute = Attribute::factory()->create(['code' => 'test_label_'.uniqid(), 'type' => 'select']);
    $locale = app()->getLocale();

    $match = AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => 'navy_'.uniqid()]);
    $match->translateOrNew($locale)->label = 'Midnight Navy';
    $match->save();

    $other = AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => 'lime_'.uniqid()]);
    $other->translateOrNew($locale)->label = 'Lime';
    $other->save();

    UnoPimAgentServer::tool(AttributeOptionSearchTool::class, [
        'filters' => [
            ['field' => 'attribute_id', 'operator' => '=', 'value' => $attribute->id],
            ['field' => 'label', 'operator' => 'CONTAINS', 'value' => 'Navy'],
        ],
    ])->assertOk()->assertSee($match->code)->assertDontSee($other->code);
});
