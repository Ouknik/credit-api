@extends('admin.layouts.app')
@section('title', 'Gateway Monitor')
@section('page-title', 'Gateway — Système de Recharge')

@section('content')

{{-- ═══ Live Status Card ═══ --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6" id="gateway-card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">État du Système (Raspberry Pi)</h3>
        <div class="flex items-center gap-2">
            <span id="status-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span>
                Chargement...
            </span>
            <button onclick="fetchHealth()" class="text-xs text-gray-500 hover:text-brand transition px-2 py-1 rounded hover:bg-gray-50">
                ↻ Actualiser
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4" id="health-grid">
        {{-- Modem --}}
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Modem</p>
            <p id="h-modem" class="text-lg font-bold text-gray-400">—</p>
        </div>
        {{-- Signal --}}
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Signal (CSQ)</p>
            <p id="h-signal" class="text-lg font-bold text-gray-400">—</p>
        </div>
        {{-- Network --}}
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Réseau (CREG)</p>
            <p id="h-network" class="text-lg font-bold text-gray-400">—</p>
        </div>
        {{-- SIM Balance --}}
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Solde SIM</p>
            <p id="h-balance" class="text-lg font-bold text-gray-400">—</p>
        </div>
        {{-- Queue --}}
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">File d'attente</p>
            <p id="h-queue" class="text-lg font-bold text-gray-400">—</p>
        </div>
        {{-- Last Check --}}
        <div class="bg-gray-50 rounded-lg p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Dernier check</p>
            <p id="h-time" class="text-lg font-bold text-gray-400">—</p>
        </div>
    </div>
</div>

{{-- ═══ Today Stats ═══ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_today'] }}</p>
        <p class="text-sm text-gray-500">Total aujourd'hui</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-2xl font-bold text-green-600">{{ $stats['success_today'] }}</p>
        <p class="text-sm text-gray-500">Réussies</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-2xl font-bold text-red-600">{{ $stats['failed_today'] }}</p>
        <p class="text-sm text-gray-500">Échouées / Refusées</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-2xl font-bold text-amber-500">{{ $stats['pending_today'] }}</p>
        <p class="text-sm text-gray-500">En cours</p>
    </div>
</div>

{{-- ═══ Recent Recharges ═══ --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold">Dernières Recharges</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Référence</th>
                    <th class="px-4 py-3 text-left">Boutique</th>
                    <th class="px-4 py-3 text-left">Téléphone</th>
                    <th class="px-4 py-3 text-left">Opérateur</th>
                    <th class="px-4 py-3 text-right">Montant</th>
                    <th class="px-4 py-3 text-center">Statut</th>
                    <th class="px-4 py-3 text-left">Message Système</th>
                    <th class="px-4 py-3 text-left">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recentRecharges as $r)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs">{{ $r->reference_code }}</td>
                    <td class="px-4 py-3">{{ $r->shop?->name ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono">{{ $r->phone }}</td>
                    <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $r->operator)) }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($r->amount, 2) }} MAD</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $statusColors = [
                                'success' => 'bg-green-100 text-green-700',
                                'failed' => 'bg-red-100 text-red-700',
                                'balance_error' => 'bg-red-100 text-red-700',
                                'rejected' => 'bg-orange-100 text-orange-700',
                                'pending' => 'bg-blue-100 text-blue-700',
                                'queued' => 'bg-blue-100 text-blue-700',
                                'processing' => 'bg-amber-100 text-amber-700',
                            ];
                            $statusLabels = [
                                'success' => 'Succès',
                                'failed' => 'Échoué',
                                'balance_error' => 'Solde insuf.',
                                'rejected' => 'Refusé',
                                'pending' => 'En attente',
                                'queued' => 'En file',
                                'processing' => 'En cours',
                            ];
                            $color = $statusColors[$r->status] ?? 'bg-gray-100 text-gray-700';
                            $label = $statusLabels[$r->status] ?? $r->status;
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                            {{ $label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate" title="{{ $r->gateway_message ?? '' }}">
                        @if($r->gateway_message)
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                {{ Str::limit($r->gateway_message, 60) }}
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $r->created_at->format('d/m H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">Aucune recharge</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CREG_LABELS = {
    '-1': 'Inconnu',
    '0': 'Pas de recherche',
    '1': 'Enregistré (Home)',
    '2': 'En recherche...',
    '3': 'Refusé ⚠️',
    '5': 'Roaming'
};

const STATUS_CONFIG = {
    'ok':         { text: 'En ligne',           color: 'bg-green-100 text-green-700', dot: 'bg-green-500' },
    'busy':       { text: 'Occupé (recharge)',  color: 'bg-blue-100 text-blue-700',   dot: 'bg-blue-500' },
    'degraded':   { text: 'Signal faible',      color: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
    'no_network': { text: 'Pas de réseau',      color: 'bg-red-100 text-red-700',     dot: 'bg-red-500' },
    'down':       { text: 'Hors ligne',         color: 'bg-red-100 text-red-700',     dot: 'bg-red-500' },
    'unreachable':{ text: 'Injoignable',        color: 'bg-gray-200 text-gray-700',   dot: 'bg-gray-500' },
};

function fetchHealth() {
    fetch('{{ route("admin.gateway.health") }}')
        .then(r => r.json())
        .then(d => updateUI(d))
        .catch(() => updateUI({ status: 'unreachable' }));
}

function updateUI(d) {
    const cfg = STATUS_CONFIG[d.status] || STATUS_CONFIG['unreachable'];

    // Badge
    document.getElementById('status-badge').className =
        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ' + cfg.color;
    document.getElementById('status-badge').innerHTML =
        '<span class="w-2 h-2 rounded-full ' + cfg.dot + '"></span>' + cfg.text;

    // Modem
    const modem = document.getElementById('h-modem');
    modem.textContent = d.modem ? 'OK ✓' : (d.status === 'unreachable' ? '?' : 'DOWN ✗');
    modem.className = 'text-lg font-bold ' + (d.modem ? 'text-green-600' : 'text-red-600');

    // Signal
    const signal = document.getElementById('h-signal');
    if (d.signal === -1) {
        signal.textContent = 'Occupé';
        signal.className = 'text-lg font-bold text-blue-500';
    } else if (d.signal !== undefined) {
        signal.textContent = d.signal + '/31';
        signal.className = 'text-lg font-bold ' + (d.signal >= 10 ? 'text-green-600' : d.signal >= 5 ? 'text-amber-500' : 'text-red-600');
    } else {
        signal.textContent = '?';
        signal.className = 'text-lg font-bold text-gray-400';
    }

    // Network (CREG)
    const network = document.getElementById('h-network');
    const cregStat = String(d.creg_stat ?? -1);
    network.textContent = CREG_LABELS[cregStat] || ('CREG=' + cregStat);
    const regOk = d.registered === true;
    network.className = 'text-lg font-bold ' + (regOk ? 'text-green-600' : cregStat === '-1' ? 'text-gray-400' : 'text-red-600');

    // Queue
    const queue = document.getElementById('h-queue');
    queue.textContent = d.queue !== undefined ? d.queue : '?';
    queue.className = 'text-lg font-bold ' + (d.queue > 0 ? 'text-amber-500' : 'text-green-600');

    // SIM Balance
    const balance = document.getElementById('h-balance');
    if (d.sim_balance !== null && d.sim_balance !== undefined) {
        balance.textContent = d.sim_balance.toFixed(2) + ' MAD';
        balance.className = 'text-lg font-bold ' + (d.sim_balance >= 50 ? 'text-green-600' : d.sim_balance >= 10 ? 'text-amber-500' : 'text-red-600');
    } else {
        balance.textContent = '—';
        balance.className = 'text-lg font-bold text-gray-400';
    }

    // Time
    document.getElementById('h-time').textContent = new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
    document.getElementById('h-time').className = 'text-lg font-bold text-gray-700';
}

// Auto-refresh every 15 seconds
fetchHealth();
setInterval(fetchHealth, 15000);
</script>
@endpush
