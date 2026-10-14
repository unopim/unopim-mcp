<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\MCP\Tools\BaseMcpTool;

#[IsDestructive]
#[IsIdempotent]
class CategoryUpsertTool extends BaseMcpTool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Create or update one or more categories in the UnoPim catalog. Automatically determines create vs update based on code existence.';

    /**
     * The tool's name.
     */
    public string $name = 'upsert_categories';

    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    protected function execute(Request $request): Response
    {
        $validated = $request->validate([
            'categories' => ['required', 'array', 'min:1', 'max:50'],
            'categories.*.code' => ['required', 'string', 'max:100'],
            'categories.*.name' => ['nullable', 'string', 'max:100'],
            'categories.*.parent_id' => ['nullable', 'integer', Rule::exists($this->categoryRepository->getModel()->getTable(), 'id')],
            'categories.*.additional_data' => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['categories'] as $index => $categoryData) {
                $code = $categoryData['code'];

                $categoryData = $this->withName($categoryData);

                $category = $this->categoryRepository->findOneByField('code', $code);

                if ($category) {
                    $this->ensureValidParent($category, $categoryData['parent_id'] ?? null, $index);

                    $category = $this->categoryRepository->update($categoryData, $category->id);
                    $results[] = ['id' => $category->id, 'code' => $category->code, 'action' => 'updated'];
                } else {
                    $category = $this->categoryRepository->create($categoryData);
                    $results[] = ['id' => $category->id, 'code' => $category->code, 'action' => 'created'];
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
     * Store `name` where UnoPim keeps category names: the requested locale's `name` field.
     * A name given explicitly in `additional_data` wins.
     */
    protected function withName(array $categoryData): array
    {
        $name = $categoryData['name'] ?? null;

        unset($categoryData['name']);

        if (filled($name)) {
            $locale = core()->getRequestedLocaleCode();

            $categoryData['additional_data']['locale_specific'][$locale]['name'] ??= $name;
        }

        return $categoryData;
    }

    /**
     * Reject a parent that is the category itself or one of its descendants, which would detach the subtree.
     *
     * @throws ValidationException
     */
    protected function ensureValidParent(Category $category, ?int $parentId, int $index): void
    {
        if (! $parentId) {
            return;
        }

        $parent = $this->categoryRepository->find($parentId);

        if ($parentId === $category->id || ($parent && $parent->isDescendantOf($category))) {
            throw ValidationException::withMessages([
                "categories.{$index}.parent_id" => trans('mcp::app.tools.catalog-writes.category-invalid-parent', ['code' => $category->code]),
            ]);
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'categories' => $schema->array()
                ->description('Array of categories to create or update (max 50).')
                ->items(
                    $schema->object([
                        'code' => $schema->string()->description('The category code. Used as unique identifier.')->required(),
                        'name' => $schema->string()->description('The category name, stored for the requested locale (additional_data.locale_specific.{locale}.name).'),
                        'parent_id' => $schema->integer()->description('The parent category ID. Must exist, and cannot be the category itself or one of its subcategories.'),
                        'additional_data' => $schema->object()->description('Additional category field values.'),
                    ])
                ),
        ];
    }
}
