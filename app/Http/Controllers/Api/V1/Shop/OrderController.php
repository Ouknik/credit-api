<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmProcurementDeliveryRequest;
use App\Http\Requests\StoreProcurementOrderRequest;
use App\Services\ProcurementOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private ProcurementOrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getOrdersByShop(
            $this->shopId(),
            (int) $request->input('per_page', 15),
            $request->only(['status'])
        );

        return $this->success($orders);
    }

    public function store(StoreProcurementOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createDraftOrder(
            $this->shopId(),
            $request->validated()
        );

        return $this->success($order, 'Order draft created successfully', 201);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->orderService->getOrderByShop($this->shopId(), $id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order);
    }

    public function publish(string $id): JsonResponse
    {
        $order = $this->orderService->publishOrder($this->shopId(), $id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        if ($order->status !== 'published') {
            return $this->error('Order can only be published from draft status with at least one item.', 422);
        }

        return $this->success($order, 'Order published successfully');
    }

    public function offers(string $id): JsonResponse
    {
        $order = $this->orderService->getOrderByShop($this->shopId(), $id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order->offers);
    }

    public function accept(string $id, string $offerId): JsonResponse
    {
        $result = $this->orderService->acceptOffer($this->shopId(), $id, $offerId);

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Unable to accept offer',
                $result['code'] ?? 400
            );
        }

        return $this->success($result['order'] ?? null, $result['message'] ?? 'Offer accepted successfully');
    }

    public function confirmDelivery(ConfirmProcurementDeliveryRequest $request, string $id): JsonResponse
    {
        $result = $this->orderService->confirmDeliveryByPin(
            $this->shopId(),
            $id,
            $request->validated()['pin']
        );

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Unable to confirm delivery',
                $result['code'] ?? 400
            );
        }

        return $this->success(
            $result['order'] ?? null,
            $result['message'] ?? 'Delivery confirmed successfully'
        );
    }
}
