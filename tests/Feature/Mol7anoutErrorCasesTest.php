<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class Mol7anoutErrorCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_wrong_pin_returns_validation_error(): void
    {
        [$ownerHeaders, $distributorHeaders] = $this->createRoleBasedAuthHeaders();
        [$orderId, $orderItems] = $this->createPublishedOrderWithItems($ownerHeaders);

        $offerId = $this->submitOffer($distributorHeaders, $orderId, $orderItems);

        $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/offers/{$offerId}/accept")
            ->assertOk();

        $this->withHeaders($distributorHeaders)
            ->putJson("/api/v1/distributor/orders/{$orderId}/status", ['status' => 'on_delivery'])
            ->assertOk();

        $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/confirm-delivery", ['pin' => '111111'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_role_is_forbidden_for_cross_domain_endpoints(): void
    {
        [$ownerHeaders, $distributorHeaders] = $this->createRoleBasedAuthHeaders();

        $this->withHeaders($ownerHeaders)
            ->postJson('/api/v1/distributor/offers', [
                'procurement_order_id' => '00000000-0000-0000-0000-000000000000',
                'items' => [],
            ])
            ->assertForbidden();

        $this->withHeaders($distributorHeaders)
            ->postJson('/api/v1/shop/orders', [
                'items' => [],
            ])
            ->assertForbidden();
    }

    public function test_duplicate_offer_returns_error(): void
    {
        [$ownerHeaders, $distributorHeaders] = $this->createRoleBasedAuthHeaders();
        [$orderId, $orderItems] = $this->createPublishedOrderWithItems($ownerHeaders);

        $this->submitOffer($distributorHeaders, $orderId, $orderItems);

        $this->withHeaders($distributorHeaders)
            ->postJson('/api/v1/distributor/offers', [
                'procurement_order_id' => $orderId,
                'delivery_cost' => 9,
                'items' => [
                    [
                        'procurement_order_item_id' => $orderItems[0]['id'],
                        'is_available' => true,
                        'unit_price' => 8.9,
                        'quantity' => 4,
                    ],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_closed_order_rejects_delivery_status_updates(): void
    {
        [$ownerHeaders, $distributorHeaders] = $this->createRoleBasedAuthHeaders();
        [$orderId, $orderItems, $pin] = $this->createPublishedOrderWithItems($ownerHeaders, includePin: true);

        $offerId = $this->submitOffer($distributorHeaders, $orderId, $orderItems);

        $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/offers/{$offerId}/accept")
            ->assertOk();

        $this->withHeaders($distributorHeaders)
            ->putJson("/api/v1/distributor/orders/{$orderId}/status", ['status' => 'on_delivery'])
            ->assertOk();

        $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/confirm-delivery", ['pin' => $pin])
            ->assertOk();

        $this->withHeaders($distributorHeaders)
            ->putJson("/api/v1/distributor/orders/{$orderId}/status", ['status' => 'preparing'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    private function createRoleBasedAuthHeaders(): array
    {
        $owner = Shop::factory()->shopOwner()->create([
            'phone' => '212633333333',
            'email' => 'owner-errors@mol7anout.test',
            'status' => 'active',
        ]);

        $distributor = Shop::factory()->distributor()->create([
            'phone' => '212644444444',
            'email' => 'distributor-errors@mol7anout.test',
            'status' => 'active',
        ]);

        $ownerHeaders = [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($owner),
            'Accept' => 'application/json',
        ];

        $distributorHeaders = [
            'Authorization' => 'Bearer ' . JWTAuth::fromUser($distributor),
            'Accept' => 'application/json',
        ];

        return [$ownerHeaders, $distributorHeaders];
    }

    private function createPublishedOrderWithItems(array $ownerHeaders, bool $includePin = false): array
    {
        $category = Category::query()->create([
            'name' => 'Errors Category',
            'slug' => 'errors-category',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Errors Product',
            'slug' => 'errors-product',
            'sku' => 'ERR-001',
            'reference_price' => 10,
            'default_unit' => 'box',
            'is_active' => true,
        ]);

        $createResponse = $this->withHeaders($ownerHeaders)
            ->postJson('/api/v1/shop/orders', [
                'delivery_address' => 'Rabat',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 4,
                        'unit' => 'box',
                    ],
                ],
            ])
            ->assertCreated();

        $orderId = (string) $createResponse->json('data.id');
        $orderItems = $createResponse->json('data.items');
        $pin = (string) $createResponse->json('data.confirmation_pin');

        $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/publish")
            ->assertOk();

        if ($includePin) {
            return [$orderId, $orderItems, $pin];
        }

        return [$orderId, $orderItems];
    }

    private function submitOffer(array $distributorHeaders, string $orderId, array $orderItems): string
    {
        $response = $this->withHeaders($distributorHeaders)
            ->postJson('/api/v1/distributor/offers', [
                'procurement_order_id' => $orderId,
                'delivery_cost' => 7.5,
                'items' => [
                    [
                        'procurement_order_item_id' => $orderItems[0]['id'],
                        'is_available' => true,
                        'unit_price' => 9.5,
                        'quantity' => 4,
                    ],
                ],
            ])
            ->assertCreated();

        return (string) $response->json('data.id');
    }
}
