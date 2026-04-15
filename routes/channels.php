<?php

use App\Models\Shop;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'prefix' => 'api',
    'middleware' => ['jwt.auth'],
]);

Broadcast::channel('shop.{shopId}', function (Shop $shop, string $shopId): bool {
    return $shop->id === $shopId;
});

Broadcast::channel('market.distributors', function (Shop $shop): array|bool {
    if (!$shop->isDistributor()) {
        return false;
    }

    return [
        'id' => $shop->id,
        'name' => $shop->name,
        'role' => $shop->role,
    ];
});
