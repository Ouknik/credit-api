<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recharge;
use App\Services\CadeauxGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    /**
     * Send admin-only Orange SIM top-up command via gateway.
     */
    public function orangeTopup(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{5,32}$/'],
        ], [
            'code.regex' => 'Le code doit contenir uniquement des chiffres (5-32).',
        ]);

        $admin = $request->user('web');
        $maskedCode = $this->maskCode($validated['code']);

        Log::info('Admin orange top-up requested', [
            'admin_id' => $admin?->id,
            'admin_email' => $admin?->email,
            'ip' => $request->ip(),
            'code' => $maskedCode,
        ]);

        try {
            $gateway = app(CadeauxGateway::class);
            $result = $gateway->adminOrangeTopup($validated['code']);

            Log::info('Admin orange top-up completed', [
                'admin_id' => $admin?->id,
                'admin_email' => $admin?->email,
                'ip' => $request->ip(),
                'code' => $maskedCode,
                'status' => $result['status'] ?? 'unknown',
                'message' => $result['message'] ?? null,
            ]);

            return redirect()
                ->route('admin.gateway.index')
                ->with('success', 'Commande Orange SIM envoyee avec succes.');
        } catch (\Throwable $e) {
            Log::warning('Admin orange top-up failed', [
                'admin_id' => $admin?->id,
                'admin_email' => $admin?->email,
                'ip' => $request->ip(),
                'code' => $maskedCode,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.gateway.index')
                ->with('error', "Echec de l'envoi: {$e->getMessage()}");
        }
    }

    private function maskCode(string $code): string
    {
        $length = strlen($code);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($code, 0, 2) . str_repeat('*', max($length - 4, 1)) . substr($code, -2);
    }
}
