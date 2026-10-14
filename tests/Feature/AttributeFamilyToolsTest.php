<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\AttributeFamilySearchTool;
use Webkul\MCP\Tools\Catalog\AttributeFamilyUpsertTool;

it('searches families through the mcp tool', function () {
    UnoPimAgentServer::tool(AttributeFamilySearchTool::class)
        ->assertOk()
        ->assertSee('families');
});

it('creates and updates families via upsert', function () {
    $code = 'tf_'.rand(100, 999);

    // Create via upsert
    $response = UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [
            [
                'code' => $code,
                'name' => 'Test Family',
            ],
        ],
    ]);

    $response->assertOk()->assertSee($code)->assertSee('created');

    $family = AttributeFamily::where('code', $code)->first();
    expect($family)->not->toBeNull();

    // Update via upsert
    UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [
            [
                'code' => $code,
                'name' => 'Updated Test Family',
            ],
        ],
    ])->assertOk()->assertSee('updated');

    // Cleanup
    if ($family) {
        $family->delete();
    }
});

it('keeps group and attribute mappings when an existing family is renamed', function () {
    $family = app(AttributeFamilyRepository::class)->createScaffolded('tf_keep_'.uniqid());

    $mappingIds = $family->attributeFamilyGroupMappings()->pluck('id')->all();
    $attributeCodes = $family->customAttributes()->pluck('code')->all();

    expect($mappingIds)->not->toBeEmpty()->and($attributeCodes)->toContain('sku');

    UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [['code' => $family->code, 'name' => 'Renamed Family']],
    ])->assertOk()->assertSee('updated');

    $family->refresh();

    expect($family->attributeFamilyGroupMappings()->pluck('id')->all())->toBe($mappingIds)
        ->and($family->customAttributes()->pluck('code')->all())->toBe($attributeCodes)
        ->and($family->name)->toBe('Renamed Family');

    $family->delete();
});

it('assigns attribute groups and attributes when creating a family', function () {
    $group = AttributeGroup::factory()->create();
    $attributes = Attribute::factory()->count(2)->create();
    $code = 'tf_new_'.uniqid();

    UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [[
            'code' => $code,
            'name' => 'Assigned Family',
            'attribute_groups' => [['code' => $group->code, 'attributes' => ['sku', ...$attributes->pluck('code')]]],
        ]],
    ])->assertOk()->assertSee('created');

    $family = AttributeFamily::where('code', $code)->firstOrFail();

    expect($family->attributeFamilyGroupMappings()->pluck('attribute_group_id')->all())->toBe([$group->id])
        ->and($family->customAttributes()->orderBy('attribute_group_mappings.position')->pluck('code')->all())
        ->toBe(['sku', ...$attributes->pluck('code')]);
});

it('adds groups and attributes to an existing family without removing any', function () {
    $family = app(AttributeFamilyRepository::class)->createScaffolded('tf_add_'.uniqid());
    $existingMapping = $family->attributeFamilyGroupMappings()->firstOrFail();
    $existingGroup = AttributeGroup::findOrFail($existingMapping->attribute_group_id);
    $before = $family->customAttributes()->pluck('code')->all();
    $newGroup = AttributeGroup::factory()->create();
    [$intoExisting, $intoNew] = Attribute::factory()->count(2)->create()->pluck('code')->all();

    UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [[
            'code' => $family->code,
            'attribute_groups' => [
                ['code' => $existingGroup->code, 'attributes' => [$intoExisting]],
                ['code' => $newGroup->code, 'attributes' => [$intoNew, 'sku']],
            ],
        ]],
    ])->assertOk()->assertSee('updated');

    $attributes = $family->customAttributes()->get();

    expect($attributes->pluck('code')->all())->toContain(...$before, ...[$intoExisting, $intoNew])
        ->and($attributes->where('code', 'sku'))->toHaveCount(1)
        ->and($attributes->firstWhere('code', $intoExisting)->group_id)->toBe($existingGroup->id)
        ->and($attributes->firstWhere('code', $intoNew)->group_id)->toBe($newGroup->id)
        ->and($family->attributeFamilyGroupMappings()->count())->toBe(count(array_unique($family->attributeFamilyGroupMappings()->pluck('attribute_group_id')->all())));
});

it('rejects unknown attribute groups or attributes without writing anything', function (array $group) {
    $code = 'tf_bad_'.uniqid();

    UnoPimAgentServer::tool(AttributeFamilyUpsertTool::class, [
        'items' => [['code' => $code, 'attribute_groups' => [$group]]],
    ])->assertHasErrors();

    expect(AttributeFamily::where('code', $code)->exists())->toBeFalse();
})->with([
    'unknown group' => [['code' => 'no_such_group', 'attributes' => ['sku']]],
    'unknown attribute' => [['code' => 'general', 'attributes' => ['no_such_attribute']]],
]);

it('advertises attribute_groups on the family upsert schema', function () {
    $item = app(AttributeFamilyUpsertTool::class)->toArray()['inputSchema']['properties']['items']['items']['properties'];

    expect($item)->toHaveKey('attribute_groups')
        ->and($item['attribute_groups']['items']['properties'])->toHaveKeys(['code', 'attributes']);
});
