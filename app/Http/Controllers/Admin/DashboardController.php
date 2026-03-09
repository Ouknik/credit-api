<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Recharge;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Overview stats
        $totalShops = Shop::count();
        $activeShops = Shop::where('status', 'active')->count();
        $suspendedShops = Shop::where('status', 'suspended')->count();
        $totalCustomers = Customer::count();
        $totalDebt = Customer::sum('total_debt');
        $totalBalance = Shop::sum('balance');

        // Today stats
        $todayDebts = Debt::whereDate('created_at', $today)->where('type', '!=', 'payment')->sum('amount');
        $todayPayments = Debt::whereDate('created_at', $today)->where('type', 'payment')->sum('amount');
        $todayRecharges = Recharge::whereDate('created_at', $today)->where('status', 'success')->sum('amount');
        $todayRechargesCount = Recharge::whereDate('created_at', $today)->where('status', 'success')->count();

        // Monthly stats
        $monthDebts = Debt::where('created_at', '>=', $thisMonth)->where('type', '!=', 'payment')->sum('amount');
        $monthPayments = Debt::where('created_at', '>=', $thisMonth)->where('type', 'payment')->sum('amount');
        $monthRecharges = Recharge::where('created_at', '>=', $thisMonth)->where('status', 'success')->sum('amount');

        // Top debtors
        $topDebtors = Customer::with('shop')
            ->where('total_debt', '>', 0)
            ->orderByDesc('total_debt')
            ->limit(10)
            ->get();

        // Recent transactions
        $recentDebts = Debt::with(['shop', 'customer'])
            ->latest()
            ->limit(15)
            ->get();

        // Revenue chart data (last 7 days)
        $chartData = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $chartData->push([
                'date' => $date->format('d/m'),
                'debts' => (float) Debt::whereDate('created_at', $date)->where('type', '!=', 'payment')->sum('amount'),
                'payments' => (float) Debt::whereDate('created_at', $date)->where('type', 'payment')->sum('amount'),
                'recharges' => (float) Recharge::whereDate('created_at', $date)->where('status', 'success')->sum('amount'),
            ]);
        }

        return view('admin.dashboard', compact(
            'totalShops', 'activeShops', 'suspendedShops',
            'totalCustomers', 'totalDebt', 'totalBalance',
            'todayDebts', 'todayPayments', 'todayRecharges', 'todayRechargesCount',
            'monthDebts', 'monthPayments', 'monthRecharges',
            'topDebtors', 'recentDebts', 'chartData'
        ));
    }
}
