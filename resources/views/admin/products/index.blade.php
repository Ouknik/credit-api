@extends('admin.layouts.app')
@section('title', 'Gestion Produits')
@section('page-title', 'Gestion Produits')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-4 gap-6 mb-6">
    <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher (nom, slug, sku)..."
                   class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand focus:border-brand">

            <select name="category_id" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand">
                <option value="">Toutes catégories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>

            <select name="status" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand">
                <option value="">Tous statuts</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
            </select>

            <button type="submit" class="px-5 py-2.5 bg-navy text-white rounded-lg text-sm font-medium hover:bg-navy/90 transition">Filtrer</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Nouveau produit</h3>
        <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-3">
            @csrf
            <select name="category_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm">
                <option value="">Catégorie</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <input name="name" required placeholder="Nom" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <input name="slug" required placeholder="Slug" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <input name="sku" placeholder="SKU (optionnel)" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <input name="default_unit" required placeholder="Unité (ex: pack)" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <input name="image_url" placeholder="Image URL" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <textarea name="description" rows="2" placeholder="Description" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"></textarea>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand focus:ring-brand">
                Actif
            </label>
            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-brand text-navy font-semibold hover:bg-brand-dark transition">Ajouter</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">Nouvelle catégorie</h3>
        <form method="POST" action="{{ route('admin.products.categories.store') }}" class="space-y-3">
            @csrf
            <input name="name" required placeholder="Nom catégorie" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <input name="slug" placeholder="Slug (optionnel)" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            <textarea name="description" rows="2" placeholder="Description (optionnel)" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"></textarea>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-brand focus:ring-brand">
                Active
            </label>
            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-navy text-white font-semibold hover:bg-navy/90 transition">Ajouter catégorie</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Produit</th>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Catégorie</th>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">SKU</th>
                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Unité</th>
                    <th class="px-5 py-3 text-center font-semibold text-gray-600">Statut</th>
                    <th class="px-5 py-3 text-center font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-900">{{ $product->name }}</p>
                            <p class="text-xs text-gray-500">{{ $product->slug }}</p>
                        </td>
                        <td class="px-5 py-3 text-gray-700">{{ $product->category?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $product->sku ?: '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $product->default_unit }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $product->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.products.edit', $product) }}" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 text-gray-700 hover:bg-gray-50">Modifier</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Supprimer ce produit ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-red-200 text-red-600 hover:bg-red-50">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">Aucun produit trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $products->links() }}</div>
    @endif
</div>
@endsection
