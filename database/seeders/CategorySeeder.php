<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'المياه والمشروبات',
                'slug' => 'water-and-beverages',
                'description' => 'مياه معدنية ومشروبات غازية وعصائر.',
            ],
            [
                'name' => 'البقالة الأساسية',
                'slug' => 'grocery-essentials',
                'description' => 'زيت، سكر، دقيق، وأساسيات المحل اليومية.',
            ],
            [
                'name' => 'منظفات وعناية',
                'slug' => 'cleaning-and-care',
                'description' => 'منظفات منزلية ومنتجات عناية شخصية.',
            ],
            [
                'name' => 'سناكات وحلويات',
                'slug' => 'snacks-and-sweets',
                'description' => 'شيبس، بسكويت، شوكولاتة ومنتجات خفيفة.',
            ],
        ];

        foreach ($categories as $payload) {
            Category::query()->updateOrCreate(
                ['slug' => $payload['slug']],
                [
                    'name' => $payload['name'],
                    'description' => $payload['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}