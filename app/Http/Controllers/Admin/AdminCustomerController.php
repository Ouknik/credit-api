<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('shop');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($shopId = $request->input('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        $sort = $request->input('sort', 'total_debt');
        $direction = $request->input('dir', 'desc');
        $query->orderBy($sort, $direction);

        $customers = $query->paginate(25)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $customer->load('shop');

        $debts = $customer->debts()
            ->latest()
            ->paginate(30);

        $totalDebtsGiven = $customer->debts()->where('type', '!=', 'payment')->sum('amount');
        $totalPayments = $customer->debts()->where('type', 'payment')->sum('amount');

        return view('admin.customers.show', compact('customer', 'debts', 'totalDebtsGiven', 'totalPayments'));
    }
}
