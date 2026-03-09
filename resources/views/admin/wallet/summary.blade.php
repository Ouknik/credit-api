@extends('admin.layouts.app')

@section('title', 'Portefeuille — Vue globale')
@section('page-title', 'Portefeuille')

@section('content')

{{-- ═══ KPI Cards ═══ --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Solde total</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($summary['total_balance'], 2) }}</p>
        <p class="text-xs text-gray-400">MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Dépôts</p>
        <p class="mt-2 text-2xl font-bold text-green-600">{{ number_format($summary['total_deposits'], 2) }}</p>
        <p class="text-xs text-gray-400">MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Recharges</p>
        <p class="mt-2 text-2xl font-bold text-blue-600">{{ number_format($summary['total_recharges'], 2) }}</p>
        <p class="text-xs text-gray-400">MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Remboursements</p>
        <p class="mt-2 text-2xl font-bold text-orange-500">{{ number_format($summary['total_refunds'], 2) }}</p>
        <p class="text-xs text-gray-400">MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ajustements</p>
        <p class="mt-2 text-2xl font-bold text-purple-600">{{ number_format($summary['total_adjustments'], 2) }}</p>
        <p class="text-xs text-gray-400">MAD</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Transactions</p>
        <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($summary['transactions_count']) }}</p>
        <p class="text-xs text-gray-400">Total</p>
    </div>
</div>

{{-- ═══ Filter + Table ═══ --}}
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-gray-900">Transactions récentes</h3>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.wallet.summary') }}"
               class="px-3 py-1.5 rounded-lg text-xs font-medium {{ !$type ? 'bg-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                Toutes
            </a>
            @foreach(['deposit' => 'Dépôts', 'recharge' => 'Recharges', 'refund' => 'Remboursements', 'adjustment' => 'Ajustements'] as $key => $label)
                <a href="{{ route('admin.wallet.summary', ['type' => $key]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium {{ $type === $key ? 'bg-navy text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 text-left font-medium">Date</th>
                    <th class="px-6 py-3 text-left font-medium">Boutique</th>
                    <th class="px-6 py-3 text-left font-medium">Type</th>
                    <th class="px-6 py-3 text-right font-medium">Montant</th>
                    <th class="px-6 py-3 text-right font-medium">Solde avant</th>
                    <th class="px-6 py-3 text-right font-medium">Solde après</th>
                    <th class="px-6 py-3 text-left font-medium">Description</th>
                    <th class="px-6 py-3 text-left font-medium">Admin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            @if($tx->shop)
                                <a href="{{ route('admin.shops.wallet.history', $tx->shop_id) }}" class="text-navy hover:underline font-medium">
                                    {{ $tx->shop->name }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap">
                            @php
                                $badges = [
                                    'deposit'    => 'bg-green-100 text-green-700',
                                    'recharge'   => 'bg-blue-100 text-blue-700',
                                    'refund'     => 'bg-orange-100 text-orange-700',
                                    'adjustment' => 'bg-purple-100 text-purple-700',
                                ];
                                $labels = [
                                    'deposit'    => 'Dépôt',
                                    'recharge'   => 'Recharge',
                                    'refund'     => 'Remboursement',
                                    'adjustment' => 'Ajustement',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badges[$tx->type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $labels[$tx->type] ?? $tx->type }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold whitespace-nowrap {{ $tx->isCredit() ? 'text-green-600' : 'text-red-600' }}">
                            {{ $tx->isCredit() ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                        </td>
                        <td class="px-6 py-3 text-right text-gray-500 whitespace-nowrap">{{ number_format($tx->balance_before, 2) }}</td>
                        <td class="px-6 py-3 text-right text-gray-500 whitespace-nowrap">{{ number_format($tx->balance_after, 2) }}</td>
                        <td class="px-6 py-3 text-gray-600 max-w-xs truncate">{{ $tx->description ?? '—' }}</td>
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $tx->admin?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            Aucune transaction trouvée.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

@endsection
