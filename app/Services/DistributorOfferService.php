<?php

namespace App\Services;

use App\Events\ProcurementRealtimeUpdated;
use App\Models\AuditLog;
use App\Models\ProcurementOffer;
use App\Models\ProcurementOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributorOfferService
{
    public function getAvailableOrders(string $distributorShopId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = ProcurementOrder::query()
            ->whereIn('status', ['published', 'receiving_offers'])
            ->where('shop_id', '!=', $distributorShopId)
            ->with([
                'shop:id,name,phone',
                'items.product:id,name,slug,default_unit,image_url,reference_price',
            ])
            ->withCount([
                'offers',
                'offers as has_submitted_offer' => fn ($q) => $q->where('distributor_shop_id', $distributorShopId),
            ]);

        if (!empty($filters['category_id'])) {
            $query->whereHas('items.product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('shop', function ($shopQuery) use ($search) {
                        $shopQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $safePerPage = max(1, min($perPage, 100));

        return $query->latest()->paginate($safePerPage);
    }

    public function getAvailableOrder(string $distributorShopId, string $orderId): ?ProcurementOrder
    {
        return ProcurementOrder::query()
            ->where('id', $orderId)
            ->whereIn('status', ['published', 'receiving_offers'])
            ->where('shop_id', '!=', $distributorShopId)
            ->with([
                'shop:id,name,phone',
                'items.product:id,name,slug,default_unit,image_url,reference_price',
                'offers' => fn ($q) => $q
                    ->where('distributor_shop_id', $distributorShopId)
                    ->with([
                        'items.product:id,name,slug,default_unit',
                        'items.orderItem:id,procurement_order_id,product_id,quantity,unit',
                    ]),
            ])
            ->first();
    }

    public function submitOffer(string $distributorShopId, array $data): array
    {
        try {
            $result = DB::transaction(function () use ($distributorShopId, $data) {
                $order = ProcurementOrder::query()
                    ->where('id', $data['procurement_order_id'])
                    ->with('items')
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return [
                        'success' => false,
                        'message' => 'Order not found',
                        'code' => 404,
                    ];
                }

                if ($order->shop_id === $distributorShopId) {
                    return [
                        'success' => false,
                        'message' => 'You cannot submit an offer on your own order.',
                        'code' => 422,
                    ];
                }

                if (!in_array($order->status, ['published', 'receiving_offers'], true)) {
                    return [
                        'success' => false,
                        'message' => 'This order is not accepting offers.',
                        'code' => 422,
                    ];
                }

                if (!$order->items()->exists()) {
                    return [
                        'success' => false,
                        'message' => 'Cannot submit an offer for an order with no items.',
                        'code' => 422,
                    ];
                }

                $offerItemsPayload = $this->buildOfferItemsPayload($order, $data['items']);

                $offer = ProcurementOffer::query()
                    ->where('procurement_order_id', $order->id)
                    ->where('distributor_shop_id', $distributorShopId)
                    ->lockForUpdate()
                    ->first();

                if ($offer && $offer->status === 'accepted') {
                    return [
                        'success' => false,
                        'message' => 'Accepted offer cannot be modified.',
                        'code' => 422,
                    ];
                }

                if ($offer) {
                    return [
                        'success' => false,
                        'message' => 'Duplicate offer is not allowed for the same order.',
                        'code' => 422,
                    ];
                }

                $deliveryCost = isset($data['delivery_cost']) ? (float) $data['delivery_cost'] : 0.0;
                $itemsTotal = (float) array_sum(array_map(
                    static fn (array $row): float => (float) $row['subtotal'],
                    $offerItemsPayload
                ));
                $totalAmount = round($itemsTotal + $deliveryCost, 2);

                $offer = new ProcurementOffer([
                    'procurement_order_id' => $order->id,
                    'distributor_shop_id' => $distributorShopId,
                ]);

                $offer->fill([
                    'status' => 'submitted',
                    'delivery_cost' => $deliveryCost,
                    'total_amount' => $totalAmount,
                    'estimated_delivery_time' => $data['estimated_delivery_time'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);
                $offer->save();

                $offer->items()->delete();
                $offer->items()->createMany($offerItemsPayload);

                if ($order->status === 'published') {
                    $order->update(['status' => 'receiving_offers']);
                }

                AuditLog::log($distributorShopId, 'procurement_offer.submitted', [
                    'procurement_order_id' => $order->id,
                    'procurement_offer_id' => $offer->id,
                    'items_count' => count($offerItemsPayload),
                    'total_amount' => $totalAmount,
                ]);

                event(new ProcurementRealtimeUpdated(
                    $order->shop_id,
                    'offer.submitted_for_owner',
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_status' => $order->status,
                        'offer_id' => $offer->id,
                        'offer_status' => 'submitted',
                    ]
                ));

                event(new ProcurementRealtimeUpdated(
                    $distributorShopId,
                    'offer.submitted_for_distributor',
                    [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'order_status' => $order->status,
                        'offer_id' => $offer->id,
                        'offer_status' => 'submitted',
                    ]
                ));

                return [
                    'success' => true,
                    'message' => 'Offer submitted successfully',
                    'created' => $offer->wasRecentlyCreated,
                    'offer' => $offer->fresh([
                        'order:id,order_number,status,shop_id',
                        'order.shop:id,name,phone',
                        'distributor:id,name,phone',
                        'items.product:id,name,slug,default_unit',
                        'items.orderItem:id,procurement_order_id,product_id,quantity,unit',
                    ]),
                ];
            });

            return $result;
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'code' => 422,
            ];
        }
    }

    public function updateDeliveryStatus(string $distributorShopId, string $orderId, string $targetStatus): array
    {
        return DB::transaction(function () use ($distributorShopId, $orderId, $targetStatus) {
            $order = ProcurementOrder::query()
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

            $acceptedOffer = $order->offers()
                ->where('status', 'accepted')
                ->first();

            if (!$acceptedOffer || $acceptedOffer->distributor_shop_id !== $distributorShopId) {
                return [
                    'success' => false,
                    'message' => 'You are not assigned to this order.',
                    'code' => 403,
                ];
            }

            if (in_array($order->status, ['delivered', 'cancelled'], true)) {
                return [
                    'success' => false,
                    'message' => 'Cannot update delivery status for a closed order.',
                    'code' => 422,
                ];
            }

            if ($targetStatus === 'preparing' && !in_array($order->status, ['accepted', 'preparing'], true)) {
                return [
                    'success' => false,
                    'message' => 'Order cannot be moved to preparing from current status.',
                    'code' => 422,
                ];
            }

            if ($targetStatus === 'on_delivery' && !in_array($order->status, ['accepted', 'preparing', 'on_delivery'], true)) {
                return [
                    'success' => false,
                    'message' => 'Order cannot be moved to on_delivery from current status.',
                    'code' => 422,
                ];
            }

            $order->update([
                'status' => $targetStatus,
            ]);

            AuditLog::log($distributorShopId, 'procurement_order.delivery_status_updated', [
                'procurement_order_id' => $order->id,
                'status' => $targetStatus,
            ]);

            event(new ProcurementRealtimeUpdated(
                $order->shop_id,
                'order.delivery_status_for_owner',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $targetStatus,
                ]
            ));

            event(new ProcurementRealtimeUpdated(
                $distributorShopId,
                'order.delivery_status_for_distributor',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $targetStatus,
                ]
            ));

            return [
                'success' => true,
                'message' => 'Delivery status updated successfully',
                'order' => $order->fresh([
                    'shop:id,name,phone',
                    'items.product:id,name,slug,default_unit',
                    'offers.distributor:id,name,phone',
                ]),
            ];
        });
    }

    private function buildOfferItemsPayload(ProcurementOrder $order, array $items): array
    {
        $orderItemsById = $order->items->keyBy('id');
        $payload = [];
        $seenOrderItemIds = [];

        foreach ($items as $index => $item) {
            $orderItemId = $item['procurement_order_item_id'] ?? null;
            if (!$orderItemId || !$orderItemsById->has($orderItemId)) {
                throw ValidationException::withMessages([
                    "items.{$index}.procurement_order_item_id" => ['Invalid order item selected.'],
                ]);
            }

            if (isset($seenOrderItemIds[$orderItemId])) {
                throw ValidationException::withMessages([
                    "items.{$index}.procurement_order_item_id" => ['Duplicate order item is not allowed in the same offer.'],
                ]);
            }
            $seenOrderItemIds[$orderItemId] = true;

            $orderItem = $orderItemsById->get($orderItemId);
            $isAvailable = (bool) ($item['is_available'] ?? true);
            $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : null;
            $quantity = array_key_exists('quantity', $item) && $item['quantity'] !== null
                ? (float) $item['quantity']
                : (float) $orderItem->quantity;

            if ($isAvailable && $unitPrice === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => ['Unit price is required when item is available.'],
                ]);
            }

            if (!$isAvailable) {
                $unitPrice = null;
                $quantity = 0.0;
            }

            $subtotal = $isAvailable ? round($unitPrice * $quantity, 2) : 0.0;

            $payload[] = [
                'procurement_order_item_id' => $orderItem->id,
                'product_id' => $orderItem->product_id,
                'is_available' => $isAvailable,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        if (count($payload) === 0) {
            throw ValidationException::withMessages([
                'items' => ['Offer must include at least one item.'],
            ]);
        }

        return $payload;
    }
}