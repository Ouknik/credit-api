@extends('admin.layouts.app')
@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')
{{-- Search --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou téléphone..."
                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand focus:border-brand">
        </div>
        <select name="sort" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm">
            <option value="total_debt" {{ request('sort', 'total_debt') === 'total_debt' ? 'selected' : '' }}>Trier par dette</option>
            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Trier par nom</option>
            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Trier par date</option>
        </select>
        <select name="dir" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm">
            <option value="desc" {{ request('dir', 'desc') === 'desc' ? 'selected' : '' }}>Décroissant</option>
            <option value="asc" {{ request('dir') === 'asc' ? 'selected' : '' }}>Croissant</option>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-navy text-white rounded-lg text-sm font-medium hover:bg-navy/90 transition">
            Filtrer
        </button>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Client</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Boutique</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Téléphone</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-600">Dette</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Limites</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Confiance</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-gray-900 hover:text-brand-dark">
                                {{ $customer->name }}
                            </a>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $customer->created_at->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.shops.show', $customer->shop) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $customer->shop->name ?? '—' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $customer->phone }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-semibold {{ $customer->total_debt > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ number_format($customer->total_debt, 2) }} MAD
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-xs text-gray-500">
                            @if($customer->max_debt_limit)
                                <span>Max: {{ number_format($customer->max_debt_limit, 0) }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($customer->is_trusted)
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-medium">Oui</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs">Non</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('admin.customers.show', $customer) }}"
                               class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-navy transition inline-flex" title="Détails">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">Aucun client trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
