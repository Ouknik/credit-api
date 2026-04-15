<?php

namespace Tests\Feature;

use App\Events\ProcurementMarketUpdated;
use App\Events\ProcurementRealtimeUpdated;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProcurementOrder;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class Mol7anoutOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_procurement_flow_from_draft_to_delivery_confirmation(): void
    {
        Event::fake([ProcurementRealtimeUpdated::class, ProcurementMarketUpdated::class]);

        [$ownerHeaders, $distributorHeaders] = $this->createRoleBasedAuthHeaders();

        $category = Category::query()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $productA = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Product A',
            'slug' => 'test-product-a',
            'sku' => 'TPA-001',
            'reference_price' => 10,
            'default_unit' => 'box',
            'is_active' => true,
        ]);

        $productB = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Test Product B',
            'slug' => 'test-product-b',
            'sku' => 'TPB-001',
            'reference_price' => 15,
            'default_unit' => 'pack',
            'is_active' => true,
        ]);

        $createOrderResponse = $this->withHeaders($ownerHeaders)
            ->postJson('/api/v1/shop/orders', [
                'delivery_address' => 'Casablanca',
                'notes' => 'Urgent order',
                'items' => [
                    [
                        'product_id' => $productA->id,
                        'quantity' => 10,
                        'unit' => 'box',
                    ],
                    [
                        'product_id' => $productB->id,
                        'quantity' => 5,
                        'unit' => 'pack',
                    ],
                ],
            ]);

        $createOrderResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft');

        $orderId = (string) $createOrderResponse->json('data.id');
        $pin = (string) $createOrderResponse->json('data.confirmation_pin');
        $orderItems = $createOrderResponse->json('data.items');

        $this->assertNotEmpty($orderId);
        $this->assertCount(2, $orderItems);
        $this->assertSame(6, strlen($pin));

        $publishResponse = $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/publish");

        $publishResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $availableResponse = $this->withHeaders($distributorHeaders)
            ->getJson('/api/v1/distributor/orders/available');

        $availableResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $availableOrderIds = collect($availableResponse->json('data.data'))->pluck('id')->all();
        $this->assertContains($orderId, $availableOrderIds);

        $submitOfferResponse = $this->withHeaders($distributorHeaders)
            ->postJson('/api/v1/distributor/offers', [
                'procurement_order_id' => $orderId,
                'delivery_cost' => 12.5,
                'estimated_delivery_time' => now()->addHours(4)->toDateTimeString(),
                'notes' => 'We can deliver quickly.',
                'items' => [
                    [
                        'procurement_order_item_id' => $orderItems[0]['id'],
                        'is_available' => true,
                        'unit_price' => 9.9,
                        'quantity' => 10,
                    ],
                    [
                        'procurement_order_item_id' => $orderItems[1]['id'],
                        'is_available' => true,
                        'unit_price' => 14.2,
                        'quantity' => 5,
                    ],
                ],
            ]);

        $submitOfferResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted');

        $offerId = (string) $submitOfferResponse->json('data.id');
        $this->assertNotEmpty($offerId);

        $offersResponse = $this->withHeaders($ownerHeaders)
            ->getJson("/api/v1/shop/orders/{$orderId}/offers");

        $offersResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(1, $offersResponse->json('data'));
        $this->assertSame($offerId, $offersResponse->json('data.0.id'));

        $acceptResponse = $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/offers/{$offerId}/accept");

        $acceptResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'accepted');

        $preparingResponse = $this->withHeaders($distributorHeaders)
            ->putJson("/api/v1/distributor/orders/{$orderId}/status", [
                'status' => 'preparing',
            ]);

        $preparingResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'preparing');

        $onDeliveryResponse = $this->withHeaders($distributorHeaders)
            ->putJson("/api/v1/distributor/orders/{$orderId}/status", [
                'status' => 'on_delivery',
            ]);

        $onDeliveryResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'on_delivery');

        $confirmResponse = $this->withHeaders($ownerHeaders)
            ->postJson("/api/v1/shop/orders/{$orderId}/confirm-delivery", [
                'pin' => $pin,
            ]);

        $confirmResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'delivered');

        $this->assertDatabaseHas('procurement_orders', [
            'id' => $orderId,
            'status' => 'delivered',
        ]);

        Event::assertDispatched(ProcurementMarketUpdated::class, function (ProcurementMarketUpdated $event) use ($orderId) {
            return $event->eventType === 'market.order_published'
                && ($event->payload['order_id'] ?? null) === $orderId;
        });

        Event::assertDispatched(ProcurementRealtimeUpdated::class, function (ProcurementRealtimeUpdated $event) use ($orderId) {
            return $event->eventType === 'offer.submitted_for_owner'
                && ($event->payload['order_id'] ?? null) === $orderId;
        });

        Event::assertDispatched(ProcurementRealtimeUpdated::class, function (ProcurementRealtimeUpdated $event) use ($orderId) {
            return $event->eventType === 'offer.accepted_for_distributor'
                && ($event->payload['order_id'] ?? null) === $orderId;
        });

        Event::assertDispatched(ProcurementRealtimeUpdated::class, function (ProcurementRealtimeUpdated $event) use ($orderId) {
            return $event->eventType === 'order.delivery_status_for_owner'
                && ($event->payload['order_id'] ?? null) === $orderId;
        });

        Event::assertDispatched(ProcurementRealtimeUpdated::class, function (ProcurementRealtimeUpdated $event) use ($orderId) {
            return $event->eventType === 'order.delivered_confirmed'
                && ($event->payload['order_id'] ?? null) === $orderId;
        });
    }

    public function test_role_based_access_is_enforced_for_shop_and_distributor_routes(): void
    {
        [$ownerHeaders, $distributorHeaders] = $this->createRoleBasedAuthHeaders();

        $this->withHeaders($ownerHeaders)
            ->getJson('/api/v1/distributor/orders/available')
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->withHeaders($distributorHeaders)
            ->getJson('/api/v1/shop/orders')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    private function createRoleBasedAuthHeaders(): array
    {
        $owner = Shop::factory()->shopOwner()->create([
            'phone' => '212611111111',
            'email' => 'owner@mol7anout.test',
            'status' => 'active',
        ]);

        $distributor = Shop::factory()->distributor()->create([
            'phone' => '212622222222',
            'email' => 'distributor@mol7anout.test',
            'status' => 'active',
        ]);

        $ownerToken = JWTAuth::fromUser($owner);
        $distributorToken = JWTAuth::fromUser($distributor);

        $ownerHeaders = [
            'Authorization' => 'Bearer ' . $ownerToken,
            'Accept' => 'application/json',
        ];

        $distributorHeaders = [
            'Authorization' => 'Bearer ' . $distributorToken,
            'Accept' => 'application/json',
        ];

        return [$ownerHeaders, $distributorHeaders];
    }
}