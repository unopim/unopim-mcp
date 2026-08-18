<?php

namespace Webkul\MCP\Tools\Catalog;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\MCP\Tools\BaseMcpTool;

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
            'categories.*.parent_id' => ['nullable', 'integer'],
            'categories.*.additional_data' => ['nullable', 'array'],
        ]);

        DB::beginTransaction();

        try {
            $results = [];

            foreach ($validated['categories'] as $categoryData) {
                $code = $categoryData['code'];

                $category = $this->categoryRepository->findOneByField('code', $code);

                if ($category) {
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
                        'name' => $schema->string()->description('The category name (required for creation).'),
                        'parent_id' => $schema->integer()->description('The parent category ID.'),
                        'status' => $schema->boolean()->description('Status (active/inactive).'),
                        'additional_data' => $schema->object()->description('Additional category field values.'),
                    ])
                ),
        ];
    }
}
