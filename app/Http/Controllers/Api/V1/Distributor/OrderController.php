<?php

namespace App\Http\Controllers\Api\V1\Distributor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProcurementDeliveryStatusRequest;
use App\Services\DistributorOfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private DistributorOfferService $distributorOfferService
    ) {}

    public function available(Request $request): JsonResponse
    {
        $orders = $this->distributorOfferService->getAvailableOrders(
            $this->shopId(),
            (int) $request->input('per_page', 15),
            $request->only(['search', 'category_id'])
        );

        return $this->success($orders);
    }

    public function show(string $id): JsonResponse
    {
        $order = $this->distributorOfferService->getAvailableOrder($this->shopId(), $id);
        if (!$order) {
            return $this->error('Order not found or not available for offering.', 404);
        }

        return $this->success($order);
    }

    public function updateStatus(UpdateProcurementDeliveryStatusRequest $request, string $id): JsonResponse
    {
        $result = $this->distributorOfferService->updateDeliveryStatus(
            $this->shopId(),
            $id,
            $request->validated()['status']
        );

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Unable to update delivery status',
                $result['code'] ?? 400
            );
        }

        return $this->success(
            $result['order'] ?? null,
            $result['message'] ?? 'Delivery status updated successfully'
        );
    }
}