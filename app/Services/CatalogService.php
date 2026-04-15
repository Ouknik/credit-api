<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    public function getCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->withCount([
                'products' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->get();
    }

    public function getProducts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with('category:id,name,slug');

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getProduct(string $id): ?Product
    {
        return Product::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->with('category:id,name,slug')
            ->first();
    }
}
