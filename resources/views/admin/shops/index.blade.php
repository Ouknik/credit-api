@extends('admin.layouts.app')
@section('title', 'Boutiques')
@section('page-title', 'Boutiques')

@section('content')
{{-- Search & Filter --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom, email, téléphone..."
                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand focus:border-brand">
        </div>
        <select name="status" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand">
            <option value="">Tous les statuts</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendue</option>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-navy text-white rounded-lg text-sm font-medium hover:bg-navy/90 transition">
            Filtrer
        </button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.shops.index') }}" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded-lg text-center">
                Réinitialiser
            </a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Boutique</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-600">Contact</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Clients</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Transactions</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-600">Solde</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Statut</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($shops as $shop)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.shops.show', $shop) }}" class="font-medium text-gray-900 hover:text-brand-dark">
                                {{ $shop->name }}
                            </a>
                            <p class="text-xs text-gray-500 mt-0.5">Inscrit {{ $shop->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-gray-700">{{ $shop->email }}</p>
                            <p class="text-xs text-gray-500">{{ $shop->phone ?? '—' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 font-semibold text-xs">
                                {{ $shop->customers_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-gray-700">{{ $shop->debts_count }} dettes</span>
                            <br>
                            <span class="text-xs text-gray-500">{{ $shop->recharges_count }} recharges</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-semibold {{ $shop->balance > 0 ? 'text-green-600' : 'text-gray-700' }}">
                                {{ number_format($shop->balance, 2) }} MAD
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $shop->status === 'active' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                {{ $shop->status === 'active' ? 'Active' : 'Suspendue' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.shops.show', $shop) }}"
                                   class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-navy transition" title="Voir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('admin.shops.toggle-status', $shop) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="p-2 rounded-lg hover:bg-gray-100 transition {{ $shop->status === 'active' ? 'text-red-500 hover:text-red-700' : 'text-green-500 hover:text-green-700' }}"
                                            title="{{ $shop->status === 'active' ? 'Suspendre' : 'Activer' }}"
                                            onclick="return confirm('Êtes-vous sûr ?')">
                                        @if($shop->status === 'active')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">Aucune boutique trouvée</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($shops->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $shops->links() }}
        </div>
    @endif
</div>
@endsection
