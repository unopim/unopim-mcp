<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Attribute\Contracts\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsIdempotent]
class AttributeFamilyUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more attribute families in UnoPim catalog. Automatically determines create vs update based on code existence. Attribute groups and attributes can be added to a family; existing assignments are never removed.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_families';

    public function __construct(
        protected AttributeFamilyRepository $familyRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeRepository $attributeRepository,
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.code' => ['required', 'string', 'max:100'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.attribute_groups' => ['nullable', 'array', 'max:50'],
            'items.*.attribute_groups.*.code' => ['required', 'string', 'distinct', 'exists:attribute_groups,code'],
            'items.*.attribute_groups.*.attributes' => ['nullable', 'array', 'max:500'],
            'items.*.attribute_groups.*.attributes.*' => ['string', 'distinct', 'exists:attributes,code'],
        ]);

        DB::beginTransaction();
        $results = [];

        try {
            foreach ($validated['items'] as $itemData) {
                $requestedGroups = $itemData['attribute_groups'] ?? [];
                unset($itemData['attribute_groups']);

                $family = $this->familyRepository->findOneByField('code', $itemData['code']);
                $action = $family ? 'updated' : 'created';

                $family ??= $this->familyRepository->create($itemData);

                $family = $this->familyRepository->update([
                    ...$itemData,
                    'attribute_groups' => $this->additiveGroupMappings($family, $requestedGroups),
                    'retained_group_mappings' => $family->attributeFamilyGroupMappings()->pluck('id')->all(),
                ], $family->id);

                $results[] = ['id' => $family->id, 'code' => $family->code, 'action' => $action];
            }

            DB::commit();

            return Response::json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Build an `attribute_groups` payload that only adds groups and attributes to the family.
     *
     * Existing mappings are restated with their current attributes because the repository detaches any attribute a restated group omits.
     *
     * @param  array<int, array{code: string, attributes?: array<int, string>}>  $requestedGroups
     * @return array<int, array<string, mixed>>
     */
    protected function additiveGroupMappings(AttributeFamily $family, array $requestedGroups): array
    {
        if ($requestedGroups === []) {
            return [];
        }

        $groupIds = $this->attributeGroupRepository->findWhereIn('code', array_column($requestedGroups, 'code'))->pluck('id', 'code');
        $attributeIds = $this->attributeRepository
            ->findWhereIn('code', collect($requestedGroups)->pluck('attributes')->flatten()->filter()->unique()->all())
            ->pluck('id', 'code');

        $mappings = $family->attributeFamilyGroupMappings()->with('customAttributes:id')->get()->keyBy('attribute_group_id');
        $assigned = $mappings->flatMap(fn ($mapping) => $mapping->customAttributes->pluck('id'))->flip();
        $nextGroupPosition = (int) $mappings->max('position') + 1;
        $payload = [];

        foreach ($requestedGroups as $group) {
            $groupId = $groupIds->get($group['code']);
            $mapping = $mappings->get($groupId);
            $attributes = $mapping
                ? $mapping->customAttributes->map(fn ($attribute): array => ['id' => $attribute->id, 'position' => $attribute->pivot->position])->all()
                : [];
            $nextAttributePosition = (int) collect($attributes)->max('position') + 1;
            $added = false;

            foreach ($group['attributes'] ?? [] as $code) {
                $attributeId = $attributeIds->get($code);

                if ($assigned->has($attributeId)) {
                    continue;
                }

                $assigned->put($attributeId, true);
                $attributes[] = ['id' => $attributeId, 'position' => $nextAttributePosition++];
                $added = true;
            }

            if ($mapping && ! $added) {
                continue;
            }

            $payload[] = array_filter([
                'id' => $groupId,
                'attribute_groups_mapping' => $mapping?->id,
                'position' => $mapping?->position ?? $nextGroupPosition++,
                'custom_attributes' => $attributes,
            ], fn ($value): bool => $value !== null);
        }

        return $payload;
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
                        'name' => $schema->string()->description('The name of the family. Updating an existing family changes only its name; its attribute groups and attributes are kept.'),
                        'attribute_groups' => $schema->array()
                            ->description('Attribute groups and attributes to add to the family. Additive: nothing already in the family is removed, and an attribute already in the family stays in its current group.')
                            ->items(
                                $schema->object([
                                    'code' => $schema->string()->description('An existing attribute group code, e.g. general.')->required(),
                                    'attributes' => $schema->array()->items($schema->string())->description('Existing attribute codes to add to this group, e.g. ["sku", "name"].'),
                                ])
                            ),
                    ])
                )->required(),
        ];
    }
}
