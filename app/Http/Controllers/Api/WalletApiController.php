<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletApiController extends Controller
{
    /**
     * Get paginated wallet transactions for the authenticated shop.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WalletTransaction::forShop($this->shopId())
            ->orderByDesc('created_at');

        // Optional type filter (deposit, recharge, refund, adjustment)
        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        $transactions = $query->paginate($request->input('per_page', 20));

        // Append recharge status for recharge/refund transactions
        $transactions->getCollection()->transform(function ($tx) {
            if (in_array($tx->type, ['recharge', 'refund']) && $tx->reference) {
                $recharge = $tx->recharge;
                $tx->setAttribute('recharge_status', $recharge?->status);
            }
            unset($tx->recharge);
            return $tx;
        });

        return $this->success($transactions);
    }
}
