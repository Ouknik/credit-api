<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Shop::withCount('customers', 'debts', 'recharges');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $shops = $query->latest()->paginate(20)->withQueryString();

        return view('admin.shops.index', compact('shops'));
    }

    public function show(Shop $shop)
    {
        $shop->loadCount('customers', 'debts', 'recharges');

        $customers = $shop->customers()
            ->orderByDesc('total_debt')
            ->paginate(20);

        $recentDebts = $shop->debts()
            ->with('customer')
            ->latest()
            ->limit(20)
            ->get();

        $recentRecharges = $shop->recharges()
            ->with('customer')
            ->latest()
            ->limit(20)
            ->get();

        // Shop financial summary
        $totalDebt = $shop->customers()->sum('total_debt');
        $totalDebtsGiven = $shop->debts()->where('type', '!=', 'payment')->sum('amount');
        $totalPaymentsReceived = $shop->debts()->where('type', 'payment')->sum('amount');
        $totalRecharges = $shop->recharges()->where('status', 'success')->sum('amount');

        return view('admin.shops.show', compact(
            'shop', 'customers', 'recentDebts', 'recentRecharges',
            'totalDebt', 'totalDebtsGiven', 'totalPaymentsReceived', 'totalRecharges'
        ));
    }

    public function toggleStatus(Shop $shop)
    {
        $shop->update([
            'status' => $shop->status === 'active' ? 'suspended' : 'active',
        ]);

        return back()->with('success', "Statut de {$shop->name} modifié avec succès.");
    }
}
