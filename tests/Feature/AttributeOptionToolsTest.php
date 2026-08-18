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
