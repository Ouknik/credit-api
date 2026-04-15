<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $shop = auth()->user();

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (!in_array($shop->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this resource.',
                'required_roles' => $roles,
                'current_role' => $shop->role,
            ], 403);
        }

        return $next($request);
    }
}