<?php

namespace App\Services;

use App\Events\ProcurementMarketUpdated;
use App\Events\ProcurementRealtimeUpdated;
use App\Models\AuditLog;
use App\Models\ProcurementOffer;
use App\Models\ProcurementOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProcurementOrderService
{
    public function getOrdersByShop(string $shopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = ProcurementOrder::query()
            ->where('shop_id', $shopId)
            ->withCount('offers')
            ->with(['items.product:id,name,slug,default_unit']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getOrderByShop(string $shopId, string $orderId): ?ProcurementOrder
    {
        return ProcurementOrder::query()
            ->where('shop_id', $shopId)
            ->where('id', $orderId)
            ->with([
                'items.product:id,name,slug,default_unit,image_url,reference_price',
                'offers.distributor:id,name,phone',
                'offers.items.product:id,name,slug,default_unit',
                'offers.items.orderItem:id,procurement_order_id,product_id,quantity,unit',
            ])
            ->first();
    }

    public function createDraftOrder(string $shopId, array $data): ProcurementOrder
    {
        return DB::transaction(function () use ($shopId, $data) {
            $order = ProcurementOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'shop_id' => $shopId,
                'status' => 'draft',
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_lat' => $data['delivery_lat'] ?? null,
                'delivery_lng' => $data['delivery_lng'] ?? null,
                'preferred_delivery_time' => $data['preferred_delivery_time'] ?? null,
                'notes' => $data['notes'] ?? null,
                'confirmation_pin' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            AuditLog::log($shopId, 'procurement_order.created', [
                'procurement_order_id' => $order->id,
                'order_number' => $order->order_number,
                'items_count' => count($data['items']),
            ]);

            return $order->load('items.product:id,name,slug,default_unit');
        });
    }

    public function publishOrder(string $shopId, string $orderId): ?ProcurementOrder
    {
        return DB::transaction(function () use ($shopId, $orderId) {
            $order = ProcurementOrder::query()
                ->where('shop_id', $shopId)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return null;
            }

            if ($order->status !== 'draft') {
                return $order->load('items.product:id,name,slug,default_unit');
            }

            if (!$order->items()->exists()) {
                return $order->load('items.product:id,name,slug,default_unit');
            }

            $order->update([
                'status' => 'published',
            ]);

            AuditLog::log($shopId, 'procurement_order.published', [
                'procurement_order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            $freshOrder = $order->fresh([
                'items.product:id,name,slug,default_unit',
                'offers.distributor:id,name,phone',
            ]);

            event(new ProcurementRealtimeUpdated(
                $shopId,
                'order.published',
                [
                    'order_id' => $freshOrder->id,
                    'order_number' => $freshOrder->order_number,
                    'order_status' => $freshOrder->status,
                ]
            ));

            event(new ProcurementMarketUpdated(
                'market.order_published',
                [
                    'order_id' => $freshOrder->id,
                    'order_number' => $freshOrder->order_number,
                    'order_status' => $freshOrder->status,
                    'shop_id' => $freshOrder->shop_id,
                ]
            ));

            return $freshOrder;
        });
    }

    public function acceptOffer(string $shopId, string $orderId, string $offerId): array
    {
        return DB::transaction(function () use ($shopId, $orderId, $offerId) {
            $order = ProcurementOrder::query()
                ->where('shop_id', $shopId)
                ->where('id', $orderId)
                ->with('offers')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found',
                    'code' => 404,
                ];
            }

            if (!in_array($order->status, ['published', 'receiving_offers'], true)) {
                return [
                    'success' => false,
                    'message' => 'Order is not in a state that allows accepting offers',
                    'code' => 422,
                ];
            }

            /** @var ProcurementOffer|null $offer */
            $offer = $order->offers->firstWhere('id', $offerId);
            if (!$offer) {
                return [
                    'success' => false,
                    'message' => 'Offer not found for this order',
                    'code' => 404,
                ];
            }

            $rejectedOffers = $order->offers
                ->where('id', '!=', $offer->id)
                ->filter(fn ($row) => !empty($row->distributor_shop_id))
                ->values();

            $order->offers()->where('id', '!=', $offer->id)->update(['status' => 'rejected']);
            $offer->update(['status' => 'accepted']);
            $order->update(['status' => 'accepted']);

            $freshOrder = $order->fresh(['offers.items', 'items.product']);

            AuditLog::log($shopId, 'procurement_offer.accepted', [
                'procurement_order_id' => $order->id,
                'procurement_offer_id' => $offer->id,
            ]);

            event(new ProcurementRealtimeUpdated(
                $shopId,
                'offer.accepted_by_shop',
                [
                    'order_id' => $freshOrder->id,
                    'order_status' => $freshOrder->status,
                    'offer_id' => $offer->id,
                    'offer_status' => 'accepted',
                ]
            ));

            event(new ProcurementRealtimeUpdated(
                $offer->distributor_shop_id,
                'offer.accepted_for_distributor',
                [
                    'order_id' => $freshOrder->id,
                    'order_status' => $freshOrder->status,
                    'offer_id' => $offer->id,
                    'offer_status' => 'accepted',
                ]
            ));

            foreach ($rejectedOffers as $rejectedOffer) {
                event(new ProcurementRealtimeUpdated(
                    (string) $rejectedOffer->distributor_shop_id,
                    'offer.rejected_for_distributor',
                    [
                        'order_id' => $freshOrder->id,
                        'order_status' => $freshOrder->status,
                        'offer_id' => $rejectedOffer->id,
                        'offer_status' => 'rejected',
                    ]
                ));
            }

            return [
                'success' => true,
                'message' => 'Offer accepted successfully',
                'order' => $freshOrder,
            ];
        });
    }

    public function confirmDeliveryByPin(string $shopId, string $orderId, string $pin): array
    {
        return DB::transaction(function () use ($shopId, $orderId, $pin) {
            $order = ProcurementOrder::query()
                ->where('shop_id', $shopId)
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found',
                    'code' => 404,
                ];
            }

            if ($order->status === 'delivered') {
                return [
                    'success' => false,
                    'message' => 'Order is already delivered.',
                    'code' => 422,
                ];
            }

            if (!in_array($order->status, ['accepted', 'preparing', 'on_delivery'], true)) {
                return [
                    'success' => false,
                    'message' => 'Order is not in a state that allows delivery confirmation.',
                    'code' => 422,
                ];
            }

            if ((string) $order->confirmation_pin !== (string) $pin) {
                return [
                    'success' => false,
                    'message' => 'Invalid confirmation PIN.',
                    'code' => 422,
                ];
            }

            $order->update([
                'status' => 'delivered',
            ]);

            $acceptedOffer = $order->offers()->where('status', 'accepted')->first();

            AuditLog::log($shopId, 'procurement_order.delivery_confirmed', [
                'procurement_order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            event(new ProcurementRealtimeUpdated(
                $shopId,
                'order.delivered_confirmed',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => 'delivered',
                ]
            ));

            if ($acceptedOffer) {
                event(new ProcurementRealtimeUpdated(
                    $acceptedOffer->distributor_shop_id,
                    'order.delivered_for_distributor',
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_status' => 'delivered',
                    ]
                ));
            }

            return [
                'success' => true,
                'message' => 'Delivery confirmed successfully',
                'order' => $order->fresh([
                    'shop:id,name,phone',
                    'items.product:id,name,slug,default_unit',
                    'offers.distributor:id,name,phone',
                ]),
            ];
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'PO-' . now()->format('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (ProcurementOrder::where('order_number', $candidate)->exists());

        return $candidate;
    }
}
