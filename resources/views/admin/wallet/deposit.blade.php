@extends('admin.layouts.app')

@section('title', "Dépôt — {$shop->name}")
@section('page-title', 'Nouveau dépôt')

@section('content')

<div class="max-w-2xl mx-auto">
    {{-- ═══ Back link ═══ --}}
    <div class="mb-6">
        <a href="{{ route('admin.shops.wallet.history', $shop) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Retour à l'historique
        </a>
    </div>

    {{-- ═══ Shop Info Card ═══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">{{ $shop->name }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $shop->email }} · {{ $shop->phone }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Solde actuel</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['current_balance'], 2) }} <span class="text-sm font-normal text-gray-400">MAD</span></p>
            </div>
        </div>

        {{-- Mini summary --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-gray-100">
            <div>
                <p class="text-xs text-gray-500">Total dépôts</p>
                <p class="text-sm font-semibold text-green-600">{{ number_format($summary['total_deposits'], 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total recharges</p>
                <p class="text-sm font-semibold text-blue-600">{{ number_format($summary['total_recharges'], 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Remboursements</p>
                <p class="text-sm font-semibold text-orange-500">{{ number_format($summary['total_refunds'], 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Ajustements</p>
                <p class="text-sm font-semibold text-purple-600">{{ number_format($summary['total_adjustments'], 2) }}</p>
            </div>
        </div>
    </div>

    {{-- ═══ Deposit Form ═══ --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Effectuer un dépôt</h3>

        <form method="POST" action="{{ route('admin.shops.wallet.deposit.submit', $shop) }}" id="depositForm">
            @csrf

            {{-- Amount --}}
            <div class="mb-5">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Montant (MAD) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="number"
                           name="amount"
                           id="amount"
                           value="{{ old('amount') }}"
                           min="100"
                           max="1000000"
                           step="0.01"
                           required
                           class="w-full px-4 py-3 border {{ $errors->has('amount') ? 'border-red-300 ring-1 ring-red-300' : 'border-gray-300' }} rounded-lg text-lg font-semibold focus:ring-2 focus:ring-brand focus:border-brand outline-none transition"
                           placeholder="0.00">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">MAD</span>
                </div>
                @error('amount')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-gray-400">Minimum : 100 MAD · Maximum : 1 000 000 MAD</p>
            </div>

            {{-- Quick amounts --}}
            <div class="mb-5">
                <p class="text-xs font-medium text-gray-500 mb-2">Montants rapides</p>
                <div class="flex flex-wrap gap-2">
                    @foreach([500, 1000, 2000, 5000, 10000, 50000] as $quick)
                        <button type="button"
                                onclick="document.getElementById('amount').value = {{ $quick }}"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
                            {{ number_format($quick) }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Description --}}
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description <span class="text-gray-400">(optionnel)</span></label>
                <textarea name="description"
                          id="description"
                          rows="3"
                          maxlength="500"
                          class="w-full px-4 py-3 border {{ $errors->has('description') ? 'border-red-300 ring-1 ring-red-300' : 'border-gray-300' }} rounded-lg text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none transition resize-none"
                          placeholder="Ex: Dépôt espèces du 15/01...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center gap-4">
                <button type="submit"
                        id="submitBtn"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand text-navy font-bold text-sm rounded-lg hover:bg-brand-dark transition shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Confirmer le dépôt
                </button>
                <a href="{{ route('admin.shops.wallet.history', $shop) }}" class="text-sm text-gray-500 hover:text-gray-700">Annuler</a>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Prevent double submit
    document.getElementById('depositForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Traitement...';
    });
</script>
@endpush
