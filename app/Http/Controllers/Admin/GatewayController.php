<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Services\CadeauxGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    /**
     * Gateway monitoring page (admin dashboard).
     */
    public function index()
    {
        // Recent recharges with all statuses
        $recentRecharges = Recharge::with('shop')
            ->latest()
            ->limit(50)
            ->get();

        // Stats
        $stats = [
            'total_today'   => Recharge::whereDate('created_at', today())->count(),
            'success_today' => Recharge::whereDate('created_at', today())->where('status', 'success')->count(),
            'failed_today'  => Recharge::whereDate('created_at', today())->whereIn('status', ['failed', 'balance_error', 'rejected'])->count(),
            'pending_today' => Recharge::whereDate('created_at', today())->whereIn('status', ['pending', 'queued', 'processing'])->count(),
        ];

        return view('admin.gateway.index', compact('recentRecharges', 'stats'));
    }

    /**
     * AJAX: fetch live gateway health from Pi.
     */
    public function health(): JsonResponse
    {
        $gateway = app(CadeauxGateway::class);
        $health = $gateway->checkHealth();

        return response()->json($health);
    }
}
