<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(
        private CatalogService $catalogService
    ) {}

    public function categories(): JsonResponse
    {
        return $this->success($this->catalogService->getCategories());
    }

    public function products(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'search']);
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        return $this->success($this->catalogService->getProducts($filters, $perPage));
    }

    public function product(string $id): JsonResponse
    {
        $product = $this->catalogService->getProduct($id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product);
    }
}
