@extends('admin.layouts.app')
@section('title', $shop->name)
@section('page-title', $shop->name)

@section('content')
{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.shops.index') }}" class="p-2 rounded-lg hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $shop->name }}</h1>
                <p class="text-sm text-gray-500">{{ $shop->email }} · {{ $shop->phone ?? '—' }}</p>
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.shops.wallet.history', $shop) }}"
           class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium border border-brand text-navy hover:bg-brand/10 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>
            Portefeuille
        </a>
        <a href="{{ route('admin.shops.wallet.deposit', $shop) }}"
           class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium bg-brand text-navy hover:bg-brand-dark transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Dépôt
        </a>
        <span class="inline-flex px-3 py-1.5 rounded-full text-sm font-medium
            {{ $shop->status === 'active' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
            {{ $shop->status === 'active' ? '● Active' : '● Suspendue' }}
        </span>
        <form method="POST" action="{{ route('admin.shops.toggle-status', $shop) }}" class="inline">
            @csrf @method('PATCH')
            <button type="submit" onclick="return confirm('Êtes-vous sûr ?')"
                    class="px-4 py-1.5 rounded-full text-sm font-medium border transition
                    {{ $shop->status === 'active' ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50' }}">
                {{ $shop->status === 'active' ? 'Suspendre' : 'Activer' }}
            </button>
        </form>
    </div>
</div>

{{-- Financial Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Solde</p>
        <p class="text-xl font-bold text-green-600 mt-1">{{ number_format($shop->balance, 2) }} MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Dettes en cours</p>
        <p class="text-xl font-bold text-red-600 mt-1">{{ number_format($totalDebt, 2) }} MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total crédits</p>
        <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($totalDebtsGiven, 2) }} MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total paiements</p>
        <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($totalPaymentsReceived, 2) }} MAD</p>
    </div>
</div>

{{-- Tabs --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Customers --}}
    <div class="xl:col-span-2 bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Clients ({{ $shop->customers_count }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-gray-600 font-medium">Nom</th>
                        <th class="px-6 py-3 text-left text-gray-600 font-medium">Téléphone</th>
                        <th class="px-6 py-3 text-right text-gray-600 font-medium">Dette</th>
                        <th class="px-6 py-3 text-center text-gray-600 font-medium">Confiance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium text-gray-900 hover:text-brand-dark">
                                    {{ $customer->name }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $customer->phone }}</td>
                            <td class="px-6 py-3 text-right">
                                <span class="font-semibold {{ $customer->total_debt > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format($customer->total_debt, 2) }} MAD
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if($customer->is_trusted)
                                    <span class="text-green-600">✓</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Aucun client</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">{{ $customers->links() }}</div>
        @endif
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Activité récente</h3>
        </div>
        <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
            @forelse($recentDebts as $debt)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs
                        {{ $debt->type === 'payment' ? 'bg-green-50 text-green-600' : ($debt->type === 'recharge' ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600') }}">
                        {{ $debt->type === 'payment' ? '↓' : ($debt->type === 'recharge' ? '📱' : '↑') }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 truncate">{{ $debt->customer->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $debt->created_at->format('d/m H:i') }}</p>
                    </div>
                    <span class="text-sm font-medium {{ $debt->type === 'payment' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $debt->type === 'payment' ? '+' : '-' }}{{ number_format($debt->amount, 2) }}
                    </span>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune activité</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
