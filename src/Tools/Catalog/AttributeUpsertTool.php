<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Rules\Code;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsIdempotent]
class AttributeUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more product attributes in the UnoPim catalog. Automatically handles create vs update based on code existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_attributes';

    /**
     * Attribute types whose options this tool manages.
     */
    protected const OPTION_TYPES = ['select', 'multiselect', 'checkbox'];

    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeOptionRepository $optionRepository,
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'attributes' => ['required', 'array', 'min:1', 'max:50'],
            'attributes.*.code' => ['required', 'string', new Code],
            'attributes.*.type' => ['nullable', 'string', new AttributeTypes],
            'attributes.*.name' => ['nullable', 'string'],
            'attributes.*.is_required' => ['nullable', 'boolean'],
            'attributes.*.is_unique' => ['nullable', 'boolean'],
            'attributes.*.value_per_locale' => ['nullable', 'boolean'],
            'attributes.*.value_per_channel' => ['nullable', 'boolean'],
            'attributes.*.options' => ['nullable', 'array'],
            'attributes.*.options.*.code' => ['required', 'string', new Code],
            'attributes.*.options.*.label' => ['nullable', 'string'],
            'attributes.*.options.*.sort_order' => ['nullable', 'integer'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['attributes'] as $index => $attributeData) {
                $code = $attributeData['code'];
                $options = $attributeData['options'] ?? null;

                unset($attributeData['options']);

                $attribute = $this->attributeRepository->findOneByField('code', $code);

                if ($attribute) {
                    if (isset($attributeData['type']) && $attributeData['type'] !== $attribute->type) {
                        throw ValidationException::withMessages([
                            "attributes.{$index}.type" => trans('mcp::app.tools.catalog-writes.attribute-type-immutable', ['code' => $code]),
                        ]);
                    }

                    unset($attributeData['type']);

                    $attribute = $this->attributeRepository->update($attributeData, $attribute->id);
                    $action = 'updated';
                } else {
                    if (empty($attributeData['type'])) {
                        DB::rollBack();

                        return Response::error("Attribute [{$code}] does not exist. The 'type' field is required for creation.");
                    }

                    $attribute = $this->attributeRepository->create($attributeData);
                    $action = 'created';
                }

                if ($options && in_array($attribute->type, self::OPTION_TYPES, true)) {
                    $this->upsertOptions($attribute, $options);
                }

                $results[] = ['id' => $attribute->id, 'code' => $attribute->code, 'action' => $action];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json(['success' => true, 'results' => $results]);
    }

    /**
     * Create or update options by code; options left out of the payload are kept.
     *
     * @param  array<int, array{code: string, label?: string|null, sort_order?: int|null}>  $options
     */
    protected function upsertOptions(Attribute $attribute, array $options): void
    {
        $existing = $this->optionRepository
            ->findWhere(['attribute_id' => $attribute->id, ['code', 'IN', array_column($options, 'code')]])
            ->keyBy('code');

        foreach ($options as $option) {
            $data = array_filter($option, fn (mixed $value): bool => $value !== null);

            if ($current = $existing->get($option['code'])) {
                $this->optionRepository->update($data, $current->id);

                continue;
            }

            $existing->put($option['code'], $this->optionRepository->create(['attribute_id' => $attribute->id, ...$data]));
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'attributes' => $schema->array()
                ->description('Array of attributes to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The attribute code. Used as unique identifier.')->required(),
                        'type' => $schema->string()->description('Attribute type: text, textarea, price, boolean, select, multiselect, datetime, date, image, gallery, file or checkbox. Required for creation and cannot be changed afterwards.'),
                        'name' => $schema->string()->description('The attribute display name (translatable, required for creation).'),
                        'is_required' => $schema->boolean()->description('Is attribute required.'),
                        'is_unique' => $schema->boolean()->description('Is attribute unique (only for text type).'),
                        'value_per_locale' => $schema->boolean()->description('Does value vary per locale.'),
                        'value_per_channel' => $schema->boolean()->description('Does value vary per channel.'),
                        'options' => $schema->array()->description('Options for select, multiselect and checkbox attributes, matched by code: existing options are updated, new ones are created, and options left out are kept.')
                            ->items($schema->object([
                                'code' => $schema->string()->description('Option code.')->required(),
                                'label' => $schema->string()->description('Option display label (translatable).'),
                                'sort_order' => $schema->integer()->description('Sort order.'),
                            ])),
                    ])
                ),
        ];
    }
}
