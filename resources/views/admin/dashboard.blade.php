@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-base font-semibold text-gray-900">Actions rapides</h3>
            <p class="text-sm text-gray-500 mt-1">Importer des produits depuis un fichier CSV sans prix.</p>
        </div>
        @if(Route::has('admin.products.import.form'))
            <div class="flex flex-col sm:flex-row gap-2">
                <a href="{{ route('admin.products.import.form') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-navy hover:bg-brand-dark transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V6m0 10.5 3.75-3.75M12 16.5l-3.75-3.75M3.75 19.5h16.5"/></svg>
                    Importer produits CSV
                </a>
                @if(Route::has('admin.products.import.template'))
                    <a href="{{ route('admin.products.import.template') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 15.75v2.25A2.25 2.25 0 006 20.25h12A2.25 2.25 0 0020.25 18v-2.25M7.5 10.5 12 15m0 0 4.5-4.5M12 15V3.75"/></svg>
                        Télécharger modèle CSV
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ═══ KPI Cards ═══ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Total Shops --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $totalShops }}</p>
        <p class="text-sm text-gray-500">Boutiques</p>
        <div class="flex gap-2 mt-2 text-xs">
            <span class="text-green-600">{{ $activeShops }} actives</span>
            @if($suspendedShops > 0)
                <span class="text-red-500">{{ $suspendedShops }} suspendues</span>
            @endif
        </div>
    </div>

    {{-- Total Customers --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $totalCustomers }}</p>
        <p class="text-sm text-gray-500">Clients</p>
    </div>

    {{-- Total Outstanding Debt --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-red-600">{{ number_format($totalDebt, 2) }} <span class="text-sm font-normal">MAD</span></p>
        <p class="text-sm text-gray-500">Dettes en cours</p>
    </div>

    {{-- Total Balance --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-green-600">{{ number_format($totalBalance, 2) }} <span class="text-sm font-normal">MAD</span></p>
        <p class="text-sm text-gray-500">Solde total boutiques</p>
    </div>
</div>

{{-- ═══ Today & Monthly Stats ═══ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Today --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-brand"></span> Aujourd'hui
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-red-50 rounded-lg p-4">
                <p class="text-sm text-red-600 font-medium">Crédits donnés</p>
                <p class="text-xl font-bold text-red-700 mt-1">{{ number_format($todayDebts, 2) }} MAD</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium">Paiements reçus</p>
                <p class="text-xl font-bold text-green-700 mt-1">{{ number_format($todayPayments, 2) }} MAD</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-600 font-medium">Recharges ({{ $todayRechargesCount }})</p>
                <p class="text-xl font-bold text-blue-700 mt-1">{{ number_format($todayRecharges, 2) }} MAD</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-600 font-medium">Solde net</p>
                <p class="text-xl font-bold {{ ($todayPayments - $todayDebts) >= 0 ? 'text-green-700' : 'text-red-700' }} mt-1">
                    {{ number_format($todayPayments - $todayDebts, 2) }} MAD
                </p>
            </div>
        </div>
    </div>

    {{-- This Month --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Ce mois
        </h3>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-red-50 rounded-lg p-4">
                <p class="text-sm text-red-600 font-medium">Crédits donnés</p>
                <p class="text-xl font-bold text-red-700 mt-1">{{ number_format($monthDebts, 2) }} MAD</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-green-600 font-medium">Paiements reçus</p>
                <p class="text-xl font-bold text-green-700 mt-1">{{ number_format($monthPayments, 2) }} MAD</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 col-span-2">
                <p class="text-sm text-blue-600 font-medium">Recharges du mois</p>
                <p class="text-xl font-bold text-blue-700 mt-1">{{ number_format($monthRecharges, 2) }} MAD</p>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Chart ═══ --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Activité des 7 derniers jours</h3>
    <div class="h-72">
        <canvas id="weekChart"></canvas>
    </div>
</div>

{{-- ═══ Bottom Grid ═══ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Top Debtors --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Top 10 Débiteurs</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($topDebtors as $i => $customer)
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

    {{-- Recent Transactions --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Dernières transactions</h3>
        </div>
        <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
            @forelse($recentDebts as $debt)
                <div class="flex items-center gap-4 px-6 py-3">
                    @if($debt->type === 'payment')
                        <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </div>
                    @elseif($debt->type === 'recharge')
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3"/></svg>
                        </div>
                    @else
                        <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $debt->customer->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $debt->shop->name ?? '' }} · {{ $debt->created_at->format('d/m H:i') }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $debt->type === 'payment' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $debt->type === 'payment' ? '+' : '-' }}{{ number_format($debt->amount, 2) }}
                    </span>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune transaction</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('weekChart').getContext('2d');
    const chartData = @json($chartData);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.map(d => d.date),
            datasets: [
                {
                    label: 'Crédits',
                    data: chartData.map(d => d.debts),
                    backgroundColor: '#FEE2E2',
                    borderColor: '#DC2626',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Paiements',
                    data: chartData.map(d => d.payments),
                    backgroundColor: '#DCFCE7',
                    borderColor: '#16A34A',
                    borderWidth: 1,
                    borderRadius: 4,
                },
                {
                    label: 'Recharges',
                    data: chartData.map(d => d.recharges),
                    backgroundColor: '#DBEAFE',
                    borderColor: '#2563EB',
                    borderWidth: 1,
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                x: { grid: { display: false } },
            },
        },
    });
</script>
@endpush
