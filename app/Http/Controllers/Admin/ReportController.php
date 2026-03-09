<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Recharge;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->input('to', Carbon::today()->toDateString());
        $shopId = $request->input('shop_id');

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        // Debts query
        $debtsQuery = Debt::whereBetween('created_at', [$fromDate, $toDate]);
        $rechargesQuery = Recharge::whereBetween('created_at', [$fromDate, $toDate])->where('status', 'success');

        if ($shopId) {
            $debtsQuery->where('shop_id', $shopId);
            $rechargesQuery->where('shop_id', $shopId);
        }

        $totalDebtsGiven = (clone $debtsQuery)->where('type', '!=', 'payment')->sum('amount');
        $totalPayments = (clone $debtsQuery)->where('type', 'payment')->sum('amount');
        $totalRecharges = (clone $rechargesQuery)->sum('amount');
        $totalRechargesCount = (clone $rechargesQuery)->count();
        $totalTransactions = (clone $debtsQuery)->count();

        // Net debt
        $netDebt = $totalDebtsGiven - $totalPayments;

        // Breakdown by type
        $manualDebts = (clone $debtsQuery)->where('type', 'manual')->sum('amount');
        $rechargeDebts = (clone $debtsQuery)->where('type', 'recharge')->sum('amount');
        $payments = (clone $debtsQuery)->where('type', 'payment')->sum('amount');

        // Daily breakdown for chart
        $dailyData = collect();
        $current = $fromDate->copy();
        while ($current->lte($toDate)) {
            $date = $current->toDateString();
            $dailyData->push([
                'date' => $current->format('d/m'),
                'debts' => (float) Debt::whereDate('created_at', $date)
                    ->where('type', '!=', 'payment')
                    ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                    ->sum('amount'),
                'payments' => (float) Debt::whereDate('created_at', $date)
                    ->where('type', 'payment')
                    ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                    ->sum('amount'),
                'recharges' => (float) Recharge::whereDate('created_at', $date)
                    ->where('status', 'success')
                    ->when($shopId, fn($q) => $q->where('shop_id', $shopId))
                    ->sum('amount'),
            ]);
            $current->addDay();
        }

        // Top shops by debt
        $topShopsByDebt = Shop::withSum(['customers as customers_total_debt' => function ($q) {
        }], 'total_debt')
            ->orderByDesc('customers_total_debt')
            ->limit(10)
            ->get()
            ->map(function ($shop) {
                $shop->customers_total_debt = $shop->customers()->sum('total_debt');
                return $shop;
            })
            ->sortByDesc('customers_total_debt')
            ->take(10);

        // Top customers by debt
        $topCustomersByDebt = Customer::with('shop')
            ->where('total_debt', '>', 0)
            ->orderByDesc('total_debt')
            ->limit(10)
            ->get();

        // Shops list for filter
        $shops = Shop::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.index', compact(
            'from', 'to', 'shopId',
            'totalDebtsGiven', 'totalPayments', 'totalRecharges',
            'totalRechargesCount', 'totalTransactions', 'netDebt',
            'manualDebts', 'rechargeDebts', 'payments',
            'dailyData',
            'topShopsByDebt', 'topCustomersByDebt',
            'shops'
        ));
    }
}
