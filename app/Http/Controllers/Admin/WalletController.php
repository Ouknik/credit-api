<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepositRequest;
use App\Models\Shop;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    // ─── DEPOSIT FORM ──────────────────────────────

    /**
     * Show the deposit form for a specific shop.
     */
    public function depositForm(Shop $shop)
    {
        $summary = $this->walletService->getShopSummary($shop->id);

        return view('admin.wallet.deposit', compact('shop', 'summary'));
    }

    /**
     * Process a manual deposit.
     */
    public function deposit(DepositRequest $request, Shop $shop)
    {
        try {
            $tx = $this->walletService->deposit(
                shopId: $shop->id,
                amount: (float) $request->validated('amount'),
                description: $request->validated('description'),
                adminId: auth()->id(),
            );

            return redirect()
                ->route('admin.shops.wallet.history', $shop)
                ->with('success', "Dépôt de {$tx->amount} MAD effectué avec succès. Nouveau solde : {$tx->balance_after} MAD.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    // ─── WALLET HISTORY (per shop) ─────────────────

    /**
     * List wallet transactions for a shop.
     */
    public function shopHistory(Request $request, Shop $shop)
    {
        $type = $request->input('type');

        $transactions = $this->walletService->getShopHistory(
            shopId: $shop->id,
            perPage: 30,
            type: $type,
        );

        $summary = $this->walletService->getShopSummary($shop->id);

        return view('admin.wallet.history', compact('shop', 'transactions', 'summary', 'type'));
    }

    // ─── GLOBAL WALLET SUMMARY ─────────────────────

    /**
     * Show global wallet dashboard.
     */
    public function summary(Request $request)
    {
        $summary = $this->walletService->getGlobalSummary();

        // Recent transactions across all shops
        $query = WalletTransaction::with(['shop:id,name', 'admin:id,name'])
            ->latest();

        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        $transactions = $query->paginate(30)->withQueryString();

        return view('admin.wallet.summary', compact('summary', 'transactions', 'type'));
    }
}
