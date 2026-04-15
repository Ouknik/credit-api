<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'category_slug' => 'water-and-beverages',
                'name' => 'ماء معدني 1.5L',
                'slug' => 'mineral-water-15l',
                'sku' => 'WTR-001',
                'description' => 'قنينة ماء معدني سعة 1.5 لتر.',
                'reference_price' => 5.50,
                'default_unit' => 'bottle',
            ],
            [
                'category_slug' => 'water-and-beverages',
                'name' => 'عصير برتقال 1L',
                'slug' => 'orange-juice-1l',
                'sku' => 'BVG-002',
                'description' => 'عصير برتقال طبيعي سعة 1 لتر.',
                'reference_price' => 12.00,
                'default_unit' => 'box',
            ],
            [
                'category_slug' => 'grocery-essentials',
                'name' => 'سكر 2Kg',
                'slug' => 'sugar-2kg',
                'sku' => 'GRC-003',
                'description' => 'سكر أبيض معبأ 2 كيلوغرام.',
                'reference_price' => 16.50,
                'default_unit' => 'pack',
            ],
            [
                'category_slug' => 'grocery-essentials',
                'name' => 'زيت مائدة 5L',
                'slug' => 'table-oil-5l',
                'sku' => 'GRC-004',
                'description' => 'زيت مائدة سعة 5 لترات.',
                'reference_price' => 72.00,
                'default_unit' => 'bottle',
            ],
            [
                'category_slug' => 'cleaning-and-care',
                'name' => 'منظف أطباق 750ml',
                'slug' => 'dish-cleaner-750ml',
                'sku' => 'CLN-005',
                'description' => 'منظف أطباق فعال 750 مل.',
                'reference_price' => 14.00,
                'default_unit' => 'bottle',
            ],
            [
                'category_slug' => 'snacks-and-sweets',
                'name' => 'شيبس عائلي 80g',
                'slug' => 'chips-family-80g',
                'sku' => 'SNK-006',
                'description' => 'رقائق بطاطس حجم عائلي 80 غ.',
                'reference_price' => 6.50,
                'default_unit' => 'pack',
            ],
        ];

        foreach ($products as $payload) {
            $category = Category::query()->where('slug', $payload['category_slug'])->first();
            if (!$category) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'category_id' => $category->id,
                    'name' => $payload['name'],
                    'sku' => $payload['sku'],
                    'description' => $payload['description'],
                    'reference_price' => $payload['reference_price'],
                    'default_unit' => $payload['default_unit'],
                    'is_active' => true,
                ]
            );
        }
    }
}