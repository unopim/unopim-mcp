<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsIdempotent]
class AttributeFamilyUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more attribute families in UnoPim catalog. Automatically determines create vs update based on code existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_families';

    public function __construct(
        protected AttributeFamilyRepository $familyRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            // Additional structure for attribute groups can be included if needed
        ]);

        DB::beginTransaction();
        $results = [];

        try {
            foreach ($validated['items'] as $itemData) {
                $code = $itemData['code'];
                $family = $this->familyRepository->findOneByField('code', $code);

                if ($family) {
                    $family = $this->familyRepository->update($itemData, $family->id);
                    $results[] = ['id' => $family->id, 'code' => $family->code, 'action' => 'updated'];
                } else {
                    $family = $this->familyRepository->create($itemData);
                    $results[] = ['id' => $family->id, 'code' => $family->code, 'action' => 'created'];
                }
            }

            DB::commit();

            return Response::json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'items' => $schema->array()
                ->description('Array of attribute families to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The unique family code (identifier).')->required(),
                        'name' => $schema->string()->description('The name of the family.'),
                        // attribute_groups structure can be added for deeper management if requested
                    ])
                )->required(),
        ];
    }
}
