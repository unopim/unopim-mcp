<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\MCP\Tools\BaseMcpTool;

class AttributeGroupUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more attribute groups in UnoPim catalog. Automatically determines create vs update based on code existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_attribute_groups';

    public function __construct(
        protected AttributeGroupRepository $groupRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();
        $results = [];

        try {
            foreach ($validated['items'] as $itemData) {
                $code = $itemData['code'];
                $group = $this->groupRepository->findOneByField('code', $code);

                if ($group) {
                    $group = $this->groupRepository->update($itemData, $group->id);
                    $results[] = ['id' => $group->id, 'code' => $group->code, 'action' => 'updated'];
                } else {
                    $group = $this->groupRepository->create($itemData);
                    $results[] = ['id' => $group->id, 'code' => $group->code, 'action' => 'created'];
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
                ->description('Array of attribute groups to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The unique group code (identifier).')->required(),
                        'name' => $schema->string()->description('The name of the group.'),
                    ])
                )->required(),
        ];
    }
}
