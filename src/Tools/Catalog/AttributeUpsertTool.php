<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Attribute\Repositories\AttributeRepository;
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

    public function __construct(
        protected AttributeRepository $attributeRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'attributes' => ['required', 'array', 'min:1', 'max:50'],
            'attributes.*.code' => ['required', 'string', 'max:100'],
            'attributes.*.type' => ['nullable', 'string'],
            'attributes.*.name' => ['nullable', 'string'],
            'attributes.*.is_required' => ['nullable', 'boolean'],
            'attributes.*.is_unique' => ['nullable', 'boolean'],
            'attributes.*.value_per_locale' => ['nullable', 'boolean'],
            'attributes.*.value_per_channel' => ['nullable', 'boolean'],
            'attributes.*.options' => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['attributes'] as $attributeData) {
                $code = $attributeData['code'];

                $attribute = $this->attributeRepository->findOneByField('code', $code);

                if ($attribute) {
                    $attribute = $this->attributeRepository->update($attributeData, $attribute->id);
                    $results[] = ['id' => $attribute->id, 'code' => $attribute->code, 'action' => 'updated'];
                } else {
                    if (empty($attributeData['type'])) {
                        return Response::error("Attribute [{$code}] does not exist. The 'type' field is required for creation.");
                    }

                    $attribute = $this->attributeRepository->create($attributeData);
                    $results[] = ['id' => $attribute->id, 'code' => $attribute->code, 'action' => 'created'];
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return Response::json(['success' => true, 'results' => $results]);
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
                        'type' => $schema->string()->description('Attribute type (text, textarea, select, multiselect, boolean, price, date, datetime, image, file, checkbox. Required for creation).'),
                        'name' => $schema->string()->description('The attribute display name (translatable, required for creation).'),
                        'is_required' => $schema->boolean()->description('Is attribute required.'),
                        'is_unique' => $schema->boolean()->description('Is attribute unique (only for text type).'),
                        'value_per_locale' => $schema->boolean()->description('Does value vary per locale.'),
                        'value_per_channel' => $schema->boolean()->description('Does value vary per channel.'),
                        'options' => $schema->array()->description('Options for select/multiselect/checkbox attributes.')
                            ->items($schema->object([
                                'code' => $schema->string()->description('Option code.'),
                                'label' => $schema->string()->description('Option display label (translatable).'),
                                'sort_order' => $schema->integer()->description('Sort order.'),
                            ])),
                    ])
                ),
        ];
    }
}
