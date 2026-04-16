@extends('admin.layouts.app')
@section('title', 'Import Produits CSV')
@section('page-title', 'Import Produits CSV')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Ajouter des produits par fichier CSV</h3>
                    <p class="text-sm text-gray-500 mt-1">Import professionnel sans prix. Le champ référence prix reste vide automatiquement.</p>
                </div>
                <a href="{{ route('admin.products.import.template') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Télécharger modèle CSV
                </a>
            </div>

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-semibold mb-2">Le fichier contient des erreurs:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.products.import.submit') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">Fichier CSV</label>
                    <input id="csv_file"
                           name="csv_file"
                           type="file"
                           required
                           accept=".csv,text/csv"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                    <p class="mt-2 text-xs text-gray-500">Taille max: 5 MB. Format: UTF-8.</p>
                </div>

                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-navy hover:bg-brand-dark transition">
                    Importer produits
                </button>
            </form>
        </div>

        @if(session('import_report'))
            @php($report = session('import_report'))
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Résultat du dernier import</h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                    <div class="rounded-lg bg-gray-50 p-4 border border-gray-200">
                        <p class="text-xs text-gray-500">Lignes traitées</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $report['processed'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-green-50 p-4 border border-green-200">
                        <p class="text-xs text-green-700">Créés</p>
                        <p class="text-2xl font-bold text-green-700">{{ $report['created'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-4 border border-blue-200">
                        <p class="text-xs text-blue-700">Mis à jour</p>
                        <p class="text-2xl font-bold text-blue-700">{{ $report['updated'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-4 border border-amber-200">
                        <p class="text-xs text-amber-700">Ignorés</p>
                        <p class="text-2xl font-bold text-amber-700">{{ $report['skipped'] ?? 0 }}</p>
                    </div>
                </div>

                @if(!empty($report['errors']))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-800 mb-2">Détails des lignes ignorées</p>
                        <ul class="list-disc pl-5 text-sm text-amber-900 space-y-1">
                            @foreach($report['errors'] as $lineError)
                                <li>{{ $lineError }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Colonnes CSV obligatoires</h3>
            <ul class="text-sm text-gray-700 space-y-2">
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">category_slug</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">name</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">slug</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">sku</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">description</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">default_unit</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">image_url</span></li>
                <li><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">is_active</span></li>
            </ul>
            <p class="mt-4 text-xs text-gray-500">Le prix n'est pas importé dans cet écran admin.</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Catégories disponibles</h3>
            <div class="space-y-2 max-h-72 overflow-auto pr-1">
                @forelse($categories as $category)
                    <div class="rounded-lg border border-gray-200 px-3 py-2">
                        <p class="text-sm font-medium text-gray-800">{{ $category->name }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $category->slug }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune catégorie trouvée.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
