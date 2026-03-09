@extends('admin.layouts.app')
@section('title', $customer->name)
@section('page-title', 'Détails Client')

@section('content')
{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
    <a href="{{ url()->previous() }}" class="p-2 rounded-lg hover:bg-gray-100 transition self-start">
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
    </a>
    <div class="flex-1">
        <h1 class="text-xl font-bold text-gray-900">{{ $customer->name }}</h1>
        <p class="text-sm text-gray-500">
            {{ $customer->phone }} ·
            Boutique:
            <a href="{{ route('admin.shops.show', $customer->shop) }}" class="text-indigo-600 hover:underline">{{ $customer->shop->name }}</a>
        </p>
    </div>
</div>

{{-- Financial Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Dette actuelle</p>
        <p class="text-2xl font-bold {{ $customer->total_debt > 0 ? 'text-red-600' : 'text-green-600' }} mt-1">
            {{ number_format($customer->total_debt, 2) }} MAD
        </p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total crédits</p>
        <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($totalDebtsGiven, 2) }} MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Total paiements</p>
        <p class="text-xl font-bold text-green-600 mt-1">{{ number_format($totalPayments, 2) }} MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500">Limite max</p>
        <p class="text-xl font-bold text-gray-900 mt-1">
            {{ $customer->max_debt_limit ? number_format($customer->max_debt_limit, 2) . ' MAD' : 'Illimitée' }}
        </p>
    </div>
</div>

{{-- Info Row --}}
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
            <span class="text-gray-500">Adresse</span>
            <p class="font-medium text-gray-900 mt-1">{{ $customer->address ?? '—' }}</p>
        </div>
        <div>
            <span class="text-gray-500">Client de confiance</span>
            <p class="font-medium mt-1 {{ $customer->is_trusted ? 'text-green-600' : 'text-gray-900' }}">
                {{ $customer->is_trusted ? 'Oui ✓' : 'Non' }}
            </p>
        </div>
        <div>
            <span class="text-gray-500">Limite journalière</span>
            <p class="font-medium text-gray-900 mt-1">{{ $customer->daily_limit ? number_format($customer->daily_limit, 2) . ' MAD' : '—' }}</p>
        </div>
        <div>
            <span class="text-gray-500">Limite mensuelle</span>
            <p class="font-medium text-gray-900 mt-1">{{ $customer->monthly_limit ? number_format($customer->monthly_limit, 2) . ' MAD' : '—' }}</p>
        </div>
    </div>
</div>

{{-- Transaction History --}}
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-900">Historique des transactions</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-gray-600 font-medium">Date</th>
                    <th class="px-6 py-3 text-left text-gray-600 font-medium">Type</th>
                    <th class="px-6 py-3 text-left text-gray-600 font-medium">Description</th>
                    <th class="px-6 py-3 text-right text-gray-600 font-medium">Montant</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($debts as $debt)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-600">
                            {{ $debt->created_at->format('d/m/Y') }}
                            <span class="text-gray-400 text-xs">{{ $debt->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-6 py-3">
                            @if($debt->type === 'payment')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-medium">
                                    ↓ Paiement
                                </span>
                            @elseif($debt->type === 'recharge')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                                    📱 Recharge
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-medium">
                                    ↑ Crédit
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $debt->description ?? '—' }}</td>
                        <td class="px-6 py-3 text-right">
                            <span class="font-semibold {{ $debt->type === 'payment' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $debt->type === 'payment' ? '+' : '-' }}{{ number_format($debt->amount, 2) }} MAD
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400">Aucune transaction</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($debts->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $debts->links() }}
        </div>
    @endif
</div>
@endsection
