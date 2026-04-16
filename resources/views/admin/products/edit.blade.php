@extends('admin.layouts.app')
@section('title', 'Modifier Produit')
@section('page-title', 'Modifier Produit')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Catégorie</label>
                <select name="category_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $product->category_id === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Unité</label>
                <input name="default_unit" value="{{ old('default_unit', $product->default_unit) }}" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Nom</label>
                <input name="name" value="{{ old('name', $product->name) }}" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">SKU</label>
                <input name="sku" value="{{ old('sku', $product->sku) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Slug</label>
            <input name="slug" value="{{ old('slug', $product->slug) }}" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Image URL</label>
            <input name="image_url" value="{{ old('image_url', $product->image_url) }}" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm"/>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm">{{ old('description', $product->description) }}</textarea>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-brand focus:ring-brand">
            Produit actif
        </label>

        <div class="pt-2 flex gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-lg bg-brand text-navy font-semibold hover:bg-brand-dark transition">Sauvegarder</button>
            <a href="{{ route('admin.products.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
