<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\MCP\Services\UnoPimQueryBuilder;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsReadOnly]
class CatalogSchemaTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Retrieve the UnoPim catalog schema: the filter fields each search tool accepts, the operators and the pagination rules.';

    /**
     * The tool's name.
     */
    public string $name = 'get_catalog_schema';

    /**
     * Product table columns search_products can filter on directly.
     */
    protected const PRODUCT_COLUMNS = [
        ['field' => 'id', 'type' => 'integer'],
        ['field' => 'sku', 'type' => 'text'],
        ['field' => 'type', 'type' => 'text'],
        ['field' => 'status', 'type' => 'boolean'],
        ['field' => 'attribute_family_id', 'type' => 'integer'],
        ['field' => 'parent_id', 'type' => 'integer'],
        ['field' => 'created_at', 'type' => 'datetime'],
        ['field' => 'updated_at', 'type' => 'datetime'],
    ];

    public function __construct(
        protected AttributeRepository $attributeRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $filterableAttributes = $this->attributeRepository
            ->with('translations')
            ->findWhere(['is_filterable' => 1], ['id', 'code', 'type', 'value_per_locale', 'value_per_channel']);

        return Response::json([
            'filterable_fields' => [
                'products' => [
                    ...self::PRODUCT_COLUMNS,
                    ...$filterableAttributes
                        ->reject(fn ($attribute): bool => $attribute->code === 'sku')
                        ->map(fn ($attribute): array => [
                            'field' => $this->productValuePath($attribute),
                            'type' => $attribute->type,
                            'label' => $attribute->name ?? $attribute->code,
                        ])
                        ->values()
                        ->all(),
                ],
                'categories' => ['id', 'code', 'parent_id', 'additional_data->common->{field}', 'additional_data->locale_specific->{locale}->{field}'],
                'attributes' => ['id', 'code', 'type', 'is_required', 'is_unique', 'is_filterable', 'name'],
                'attribute_options' => ['id', 'attribute_id', 'code', 'sort_order', 'label'],
                'attribute_groups' => ['id', 'code', 'name'],
                'families' => ['id', 'code', 'status', 'name'],
                'channels' => ['id', 'code'],
                'locales' => ['id', 'code', 'status'],
            ],
            'placeholders' => 'Replace {channel}, {locale} and {currency} with real codes (see search_settings), and {field} with a category field code.',
            'operators' => UnoPimQueryBuilder::OPERATORS,
            'pagination' => [
                'limit_max' => 100,
                'type' => 'cursor',
            ],
        ]);
    }

    /**
     * The JSON path search_products filters on for an attribute, following how product values are scoped.
     */
    protected function productValuePath(Attribute $attribute): string
    {
        $path = match (true) {
            $attribute->value_per_channel && $attribute->value_per_locale => 'values->channel_locale_specific->{channel}->{locale}->',
            (bool) $attribute->value_per_channel => 'values->channel_specific->{channel}->',
            (bool) $attribute->value_per_locale => 'values->locale_specific->{locale}->',
            default => 'values->common->',
        };

        return $path.$attribute->code.($attribute->type === 'price' ? '->{currency}' : '');
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
