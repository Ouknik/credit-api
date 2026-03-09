<?php

namespace App\Services;

use App\Models\Shop;
use App\Repositories\ShopRepository;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private ShopRepository $shopRepository
    ) {}

    public function register(array $data): array
    {
        $shop = $this->shopRepository->create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'balance' => 0,
            'status' => 'active',
        ]);

        $token = JWTAuth::fromUser($shop);

        AuditLog::log($shop->id, 'shop.registered', [
            'email' => $shop->email,
        ]);

        return [
            'shop' => $shop,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function login(string $email, string $password): ?array
    {
        $shop = $this->shopRepository->findByEmail($email);

        if (!$shop || !Hash::check($password, $shop->password)) {
            return null;
        }

        if (!$shop->isActive()) {
            return null;
        }

        $token = JWTAuth::fromUser($shop);

        AuditLog::log($shop->id, 'shop.login', [
            'email' => $shop->email,
        ]);

        return [
            'shop' => $shop,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function logout(): void
    {
        $shop = auth()->user();
        
        if ($shop) {
            AuditLog::log($shop->id, 'shop.logout', []);
        }

        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function refresh(): array
    {
        $shop = auth()->user();
        $token = JWTAuth::refresh(JWTAuth::getToken());
        
        return [
            'shop' => $shop,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function me(): ?Shop
    {
        return auth()->user();
    }
}
