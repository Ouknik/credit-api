<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

class ProductImportController extends Controller
{
    /** @var array<int, string> */
    private array $requiredHeaders = [
        'category_slug',
        'name',
        'slug',
        'description',
        'default_unit',
        'image_url',
        'is_active',
    ];

    /** @var array<int, string> */
    private array $requiredCategoryHeaders = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    /** @var array<int, string> */
    private array $requiredUnifiedHeaders = [
        'row_type',
        'category_slug',
        'name',
        'slug',
        'sku',
        'description',
        'default_unit',
        'image_url',
        'is_active',
    ];

    public function showImportForm()
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['name', 'slug']);

        return view('admin.products.import', compact('categories'));
    }

    public function downloadTemplate()
    {
        $templatePath = base_path('database/seeders/data/products_no_price.csv');

        if (!is_file($templatePath)) {
            return back()->with('error', 'Fichier modèle introuvable.');
        }

        return response()->download(
            $templatePath,
            'products_no_price_template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function downloadCategoryTemplate()
    {
        $templatePath = base_path('database/seeders/data/categories_template.csv');

        if (!is_file($templatePath)) {
            return back()->with('error', 'Fichier modèle catégories introuvable.');
        }

        return response()->download(
            $templatePath,
            'categories_template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function downloadUnifiedTemplate()
    {
        $templatePath = base_path('database/seeders/data/unified_catalog_template.csv');

        if (!is_file($templatePath)) {
            return back()->with('error', 'Fichier modèle unifié introuvable.');
        }

        return response()->download(
            $templatePath,
            'unified_catalog_template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $filePath = $request->file('csv_file')?->getRealPath();
        if (!$filePath) {
            return back()->with('error', 'Impossible de lire le fichier envoyé.');
        }

        $report = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $csv = new SplFileObject($filePath);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $line = 0;
        $header = null;
        $categoriesBySlug = Category::query()->pluck('id', 'slug')->all();

        foreach ($csv as $row) {
            $line++;

            if ($row === false || $row === [null]) {
                continue;
            }

            $row = array_map(
                static fn ($value) => is_string($value) ? trim($value) : $value,
                $row
            );

            if ($header === null) {
                $header = $this->normalizeHeader($row);

                $missing = array_diff($this->requiredHeaders, $header);
                if (!empty($missing)) {
                    return back()->with('error', 'Colonnes manquantes: ' . implode(', ', $missing));
                }

                continue;
            }

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $report['processed']++;

            $entry = $this->buildEntry($header, $row);

            try {
                $result = $this->upsertProductFromEntry($entry, $categoriesBySlug);
                $report[$result]++;
            } catch (Throwable $e) {
                $report['skipped']++;
                if (count($report['errors']) < 25) {
                    $report['errors'][] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
        }

        $summary = sprintf(
            'Import terminé: %d créé(s), %d mis à jour, %d ignoré(s).',
            $report['created'],
            $report['updated'],
            $report['skipped']
        );

        return redirect()
            ->route('admin.products.import.form')
            ->with('success', $summary)
            ->with('import_report', $report);
    }

    public function importCategories(Request $request): RedirectResponse
    {
        $request->validate([
            'categories_csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $filePath = $request->file('categories_csv_file')?->getRealPath();
        if (!$filePath) {
            return back()->with('error', 'Impossible de lire le fichier catégories envoyé.');
        }

        $report = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $csv = new SplFileObject($filePath);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $line = 0;
        $header = null;

        foreach ($csv as $row) {
            $line++;

            if ($row === false || $row === [null]) {
                continue;
            }

            $row = array_map(
                static fn ($value) => is_string($value) ? trim($value) : $value,
                $row
            );

            if ($header === null) {
                $header = $this->normalizeHeader($row);

                $missing = array_diff($this->requiredCategoryHeaders, $header);
                if (!empty($missing)) {
                    return back()->with('error', 'Colonnes catégories manquantes: ' . implode(', ', $missing));
                }

                continue;
            }

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $report['processed']++;

            $entry = $this->buildEntry($header, $row);

            try {
                $result = $this->upsertCategoryFromEntry($entry);
                $report[$result]++;
            } catch (Throwable $e) {
                $report['skipped']++;
                if (count($report['errors']) < 25) {
                    $report['errors'][] = "Ligne {$line}: {$e->getMessage()}";
                }
            }
        }

        $summary = sprintf(
            'Import catégories terminé: %d créé(s), %d mis à jour, %d ignoré(s).',
            $report['created'],
            $report['updated'],
            $report['skipped']
        );

        return redirect()
            ->route('admin.products.import.form')
            ->with('success', $summary)
            ->with('category_import_report', $report);
    }

    public function importUnified(Request $request): RedirectResponse
    {
        $request->validate([
            'unified_csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $filePath = $request->file('unified_csv_file')?->getRealPath();
        if (!$filePath) {
            return back()->with('error', 'Impossible de lire le fichier unifié envoyé.');
        }

        $report = [
            'total_rows' => 0,
            'unknown_rows' => 0,
            'categories' => [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ],
            'products' => [
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ],
            'errors' => [],
        ];

        $csv = new SplFileObject($filePath);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $line = 0;
        $header = null;
        $categoryEntries = [];
        $productEntries = [];

        foreach ($csv as $row) {
            $line++;

            if ($row === false || $row === [null]) {
                continue;
            }

            $row = array_map(
                static fn ($value) => is_string($value) ? trim($value) : $value,
                $row
            );

            if ($header === null) {
                $header = $this->normalizeHeader($row);

                $missing = array_diff($this->requiredUnifiedHeaders, $header);
                if (!empty($missing)) {
                    return back()->with('error', 'Colonnes unifiées manquantes: ' . implode(', ', $missing));
                }

                continue;
            }

            if ($this->isRowEmpty($row)) {
                continue;
            }

            $report['total_rows']++;

            $entry = $this->buildEntry($header, $row);
            $rowType = strtolower(trim((string) ($entry['row_type'] ?? '')));

            if (in_array($rowType, ['category', 'cat'], true)) {
                $categoryEntries[] = ['line' => $line, 'entry' => $entry];
                continue;
            }

            if (in_array($rowType, ['product', 'prod'], true)) {
                $productEntries[] = ['line' => $line, 'entry' => $entry];
                continue;
            }

            $report['unknown_rows']++;
            if (count($report['errors']) < 25) {
                $report['errors'][] = "Ligne {$line}: row_type invalide ({$rowType}). Utiliser category ou product.";
            }
        }

        foreach ($categoryEntries as $item) {
            $report['categories']['processed']++;

            try {
                $result = $this->upsertCategoryFromEntry($item['entry']);
                $report['categories'][$result]++;
            } catch (Throwable $e) {
                $report['categories']['skipped']++;
                if (count($report['errors']) < 25) {
                    $report['errors'][] = 'Ligne ' . $item['line'] . ' [category]: ' . $e->getMessage();
                }
            }
        }

        $categoriesBySlug = Category::query()->pluck('id', 'slug')->all();

        foreach ($productEntries as $item) {
            $report['products']['processed']++;

            try {
                $result = $this->upsertProductFromEntry($item['entry'], $categoriesBySlug);
                $report['products'][$result]++;
            } catch (Throwable $e) {
                $report['products']['skipped']++;
                if (count($report['errors']) < 25) {
                    $report['errors'][] = 'Ligne ' . $item['line'] . ' [product]: ' . $e->getMessage();
                }
            }
        }

        $summary = sprintf(
            'Import unifié terminé: catégories %d créées/%d mises à jour, produits %d créés/%d mis à jour, %d lignes ignorées.',
            $report['categories']['created'],
            $report['categories']['updated'],
            $report['products']['created'],
            $report['products']['updated'],
            $report['categories']['skipped'] + $report['products']['skipped'] + $report['unknown_rows']
        );

        return redirect()
            ->route('admin.products.import.form')
            ->with('success', $summary)
            ->with('unified_import_report', $report);
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, string>
     */
    private function normalizeHeader(array $row): array
    {
        if (isset($row[0]) && is_string($row[0])) {
            $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]) ?? $row[0];
        }

        return array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            $row
        );
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildEntry(array $header, array $row): array
    {
        $assoc = [];

        foreach ($header as $index => $column) {
            $assoc[$column] = Arr::get($row, $index);
        }

        return $assoc;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string>  $categoriesBySlug
     */
    private function upsertProductFromEntry(array $entry, array $categoriesBySlug): string
    {
        $categorySlug = trim((string) ($entry['category_slug'] ?? ''));
        $name = trim((string) ($entry['name'] ?? ''));
        $slug = trim((string) ($entry['slug'] ?? ''));
        $sku = trim((string) ($entry['sku'] ?? ''));
        $defaultUnit = trim((string) ($entry['default_unit'] ?? ''));

        if ($categorySlug === '' || $name === '' || $defaultUnit === '') {
            throw new \RuntimeException('Champs requis vides (category_slug, name, default_unit).');
        }

        if ($slug === '') {
            $slug = Str::slug($name);
            if ($slug === '') {
                throw new \RuntimeException('Slug introuvable: fournir slug ou un name valide.');
            }
        }

        $categoryId = $categoriesBySlug[$categorySlug] ?? null;
        if (!$categoryId) {
            throw new \RuntimeException("Catégorie introuvable: {$categorySlug}");
        }

        $isActiveValue = trim((string) ($entry['is_active'] ?? ''));
        $isActive = $isActiveValue === '' ? true : $this->toBoolean($isActiveValue);
        if ($isActive === null) {
            throw new \RuntimeException('Valeur is_active invalide (utiliser true/false ou 1/0).');
        }

        $payload = [
            'category_id' => $categoryId,
            'name' => $name,
            'sku' => $sku !== '' ? $sku : null,
            'description' => trim((string) ($entry['description'] ?? '')),
            'image_url' => trim((string) ($entry['image_url'] ?? '')),
            'default_unit' => $defaultUnit,
            'is_active' => $isActive,
            'reference_price' => null,
        ];

        $existing = Product::query()->where('slug', $slug)->first();

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return 'updated';
        }

        Product::query()->create(array_merge(['slug' => $slug], $payload));

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function upsertCategoryFromEntry(array $entry): string
    {
        $name = trim((string) ($entry['name'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Le champ name est obligatoire.');
        }

        $incomingSlug = trim((string) ($entry['slug'] ?? ''));
        $baseSlug = Str::slug($incomingSlug !== '' ? $incomingSlug : $name);
        $slug = $baseSlug !== '' ? $baseSlug : 'category';

        $isActiveValue = trim((string) ($entry['is_active'] ?? ''));
        $isActive = $isActiveValue === '' ? true : $this->toBoolean($isActiveValue);
        if ($isActive === null) {
            throw new \RuntimeException('Valeur is_active invalide (utiliser true/false ou 1/0).');
        }

        $description = trim((string) ($entry['description'] ?? ''));

        $existing = Category::query()->where('slug', $slug)->first();

        if ($existing) {
            $existing->fill([
                'name' => $name,
                'description' => $description,
                'is_active' => $isActive,
            ]);
            $existing->save();

            return 'updated';
        }

        $uniqueSlug = $slug;
        $counter = 2;
        while (Category::query()->where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slug . '-' . $counter;
            $counter++;
        }

        Category::query()->create([
            'name' => $name,
            'slug' => $uniqueSlug,
            'description' => $description,
            'is_active' => $isActive,
        ]);

        return 'created';
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function toBoolean(mixed $value): ?bool
    {
        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'oui', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'non', 'off'], true)) {
            return false;
        }

        return null;
    }
}
