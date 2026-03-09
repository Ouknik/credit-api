@extends('admin.layouts.app')
@section('title', 'Rapports Financiers')
@section('page-title', 'Rapports Financiers')

@section('content')
{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Du</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Au</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Boutique</label>
            <select name="shop_id" class="px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-brand">
                <option value="">Toutes les boutiques</option>
                @foreach($shops as $shop)
                    <option value="{{ $shop->id }}" {{ $shopId === $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-navy text-white rounded-lg text-sm font-medium hover:bg-navy/90 transition">
            Générer
        </button>
        <a href="{{ route('admin.reports.index') }}" class="px-4 py-2.5 text-sm text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            Réinitialiser
        </a>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-red-500"></span> Total crédits donnés
        </p>
        <p class="text-2xl font-bold text-red-600 mt-2">{{ number_format($totalDebtsGiven, 2) }} <span class="text-sm font-normal">MAD</span></p>
        <div class="mt-2 text-xs text-gray-500 space-y-1">
            <p>Manuel: {{ number_format($manualDebts, 2) }} MAD</p>
            <p>Recharge: {{ number_format($rechargeDebts, 2) }} MAD</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500"></span> Total paiements reçus
        </p>
        <p class="text-2xl font-bold text-green-600 mt-2">{{ number_format($totalPayments, 2) }} <span class="text-sm font-normal">MAD</span></p>
        <div class="mt-2 text-xs text-gray-500">
            <p>{{ $totalTransactions }} transactions total</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-sm text-gray-500 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Dette nette (période)
        </p>
        <p class="text-2xl font-bold {{ $netDebt > 0 ? 'text-red-600' : 'text-green-600' }} mt-2">
            {{ number_format($netDebt, 2) }} <span class="text-sm font-normal">MAD</span>
        </p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 col-span-2 lg:col-span-1">
        <p class="text-sm text-gray-500 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span> Recharges réussies
        </p>
        <p class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($totalRecharges, 2) }} <span class="text-sm font-normal">MAD</span></p>
        <div class="mt-2 text-xs text-gray-500">
            <p>{{ $totalRechargesCount }} recharges</p>
        </div>
    </div>
</div>

{{-- Chart --}}
@if($dailyData->count() <= 60)
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Évolution sur la période</h3>
    <div class="h-72">
        <canvas id="reportChart"></canvas>
    </div>
</div>
@endif

{{-- Bottom: Top debtors --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Top Shops --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Top 10 Boutiques par dette client</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($topShopsByDebt as $i => $shop)
                <a href="{{ route('admin.shops.show', $shop) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 transition">
                    <span class="w-7 h-7 rounded-full bg-navy/10 text-navy text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $shop->name }}</p>
                        <p class="text-xs text-gray-500">{{ $shop->customers()->count() }} clients</p>
                    </div>
                    <span class="text-sm font-semibold text-red-600">{{ number_format($shop->customers_total_debt, 2) }} MAD</span>
                </a>
            @empty
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune donnée</p>
            @endforelse
        </div>
    </div>

    {{-- Top Customers --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Top 10 Clients débiteurs</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($topCustomersByDebt as $i => $customer)
                <a href="{{ route('admin.customers.show', $customer) }}" class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 transition">
                    <span class="w-7 h-7 rounded-full bg-red-50 text-red-600 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $customer->name }}</p>
                        <p class="text-xs text-gray-500">{{ $customer->shop->name ?? '—' }}</p>
                    </div>
                    <span class="text-sm font-semibold text-red-600">{{ number_format($customer->total_debt, 2) }} MAD</span>
                </a>
            @empty
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucun débiteur</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($dailyData->count() <= 60)
<script>
    const ctx = document.getElementById('reportChart').getContext('2d');
    const data = @json($dailyData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [
                {
                    label: 'Crédits',
                    data: data.map(d => d.debts),
                    borderColor: '#DC2626',
                    backgroundColor: 'rgba(220,38,38,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: data.length > 30 ? 0 : 3,
                },
                {
                    label: 'Paiements',
                    data: data.map(d => d.payments),
                    borderColor: '#16A34A',
                    backgroundColor: 'rgba(22,163,74,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: data.length > 30 ? 0 : 3,
                },
                {
                    label: 'Recharges',
                    data: data.map(d => d.recharges),
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: data.length > 30 ? 0 : 3,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                x: { grid: { display: false } },
            },
        },
    });
</script>
@endif
@endpush
