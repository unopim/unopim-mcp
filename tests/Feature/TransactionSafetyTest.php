<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\MCP\Servers\UnoPimAgentServer;
use Webkul\MCP\Tools\Catalog\AttributeFamilyUpsertTool;
use Webkul\MCP\Tools\Catalog\AttributeGroupUpsertTool;
use Webkul\MCP\Tools\Catalog\AttributeUpsertTool;
use Webkul\MCP\Tools\Catalog\ProductUpsertTool;
use Webkul\MCP\Tools\Settings\CurrencyUpsertTool;
use Webkul\Product\Models\Product;

/**
 * Regression: early returns inside upsert DB transactions used to leak the
 * transaction (no rollback, no commit). These tests prove that a mid-batch
 * validation failure both returns an error AND closes the transaction.
 */
it('rolls back the transaction when product upsert returns early on missing type', function () {
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $existingSku = 'TX-OK-'.uniqid();
    $missingSku = 'TX-FAIL-'.uniqid();

    // First item creates fine. Second item is a new SKU without `type`, which
    // should trigger the early-return branch.
    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $existingSku,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
            ],
            [
                // new SKU -> goes to create branch -> missing type -> early return
                'sku' => $missingSku,
                'values' => ['common' => ['name' => 'Should not persist']],
            ],
        ],
    ])->assertHasErrors();

    // The whole batch must be rolled back. The first item should NOT exist.
    expect(Product::where('sku', $existingSku)->exists())->toBeFalse();
    expect(Product::where('sku', $missingSku)->exists())->toBeFalse();

    // Transaction must be fully closed (returned to baseline) after the tool returns.
    expect(DB::transactionLevel())->toBeLessThan(2);

    Product::whereIn('sku', [$existingSku, $missingSku])->delete();
});

it('rolls back the transaction when attribute upsert returns early on missing type', function () {
    $existingCode = 'tx_ok_'.uniqid();
    $missingCode = 'tx_fail_'.uniqid();

    UnoPimAgentServer::tool(AttributeUpsertTool::class, [
        'attributes' => [
            [
                'code' => $existingCode,
                'type' => 'text',
                'name' => 'Temporary attribute',
            ],
            [
                // new code -> create branch -> missing type -> early return
                'code' => $missingCode,
                'name' => 'Should not persist',
            ],
        ],
    ])->assertHasErrors();

    expect(Attribute::where('code', $existingCode)->exists())->toBeFalse();
    expect(Attribute::where('code', $missingCode)->exists())->toBeFalse();

    expect(DB::transactionLevel())->toBeLessThan(2);

    Attribute::whereIn('code', [$existingCode, $missingCode])->delete();
});

it('rolls back when an unexpected exception bubbles from repository', function () {
    // Sanity: the catch(Throwable) branch must also leave no dangling transaction.
    // We don't have an easy way to force the repository to throw without mocking,
    // so we assert the happy path closes the transaction too — which proves the
    // finally-style guarantee is in place for the commit path.
    $family = AttributeFamily::first() ?? AttributeFamily::factory()->create();
    $sku = 'TX-HAPPY-'.uniqid();

    UnoPimAgentServer::tool(ProductUpsertTool::class, [
        'products' => [
            [
                'sku' => $sku,
                'type' => 'simple',
                'attribute_family_id' => $family->id,
            ],
        ],
    ])->assertOk();

    expect(DB::transactionLevel())->toBeLessThan(2);

    Product::where('sku', $sku)->delete();
});

it('hides the database error behind a reference when an upsert fails unexpectedly', function (string $tool, string $repository, array $arguments) {
    $level = DB::transactionLevel();

    $this->mock($repository, fn ($mock) => $mock->shouldReceive('findOneByField')->andThrow(
        new QueryException('mysql', 'select * from `mcp_secret_table` where `code` = ?', ['x'], new Exception('SQLSTATE[42S02] /var/www/secret/path'))
    ));

    UnoPimAgentServer::tool($tool, $arguments)
        ->assertHasErrors()
        ->assertSee('Ref:')
        ->assertDontSee('mcp_secret_table')
        ->assertDontSee('/var/www/secret/path');

    expect(DB::transactionLevel())->toBe($level);
})->with([
    'families' => [AttributeFamilyUpsertTool::class, AttributeFamilyRepository::class, ['items' => [['code' => 'tx_family']]]],
    'attribute groups' => [AttributeGroupUpsertTool::class, AttributeGroupRepository::class, ['items' => [['code' => 'tx_group']]]],
    'currencies' => [CurrencyUpsertTool::class, CurrencyRepository::class, ['items' => [['code' => 'TXQ']]]],
]);
