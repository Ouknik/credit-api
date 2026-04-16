<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use SplFileObject;
use Throwable;

class ProductImportController extends Controller
{
    /** @var array<int, string> */
    private array $requiredHeaders = [
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

        if ($categorySlug === '' || $name === '' || $slug === '' || $sku === '' || $defaultUnit === '') {
            throw new \RuntimeException('Champs requis vides (category_slug, name, slug, sku, default_unit).');
        }

        $categoryId = $categoriesBySlug[$categorySlug] ?? null;
        if (!$categoryId) {
            throw new \RuntimeException("Catégorie introuvable: {$categorySlug}");
        }

        $isActive = $this->toBoolean($entry['is_active'] ?? true);
        if ($isActive === null) {
            throw new \RuntimeException('Valeur is_active invalide (utiliser true/false ou 1/0).');
        }

        $payload = [
            'category_id' => $categoryId,
            'name' => $name,
            'sku' => $sku,
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
