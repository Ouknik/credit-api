<?php

namespace App\Http\Controllers\Api\V1\Distributor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcurementOfferRequest;
use App\Services\DistributorOfferService;
use Illuminate\Http\JsonResponse;

class OfferController extends Controller
{
    public function __construct(
        private DistributorOfferService $distributorOfferService
    ) {}

    public function store(StoreProcurementOfferRequest $request): JsonResponse
    {
        $result = $this->distributorOfferService->submitOffer(
            $this->shopId(),
            $request->validated()
        );

        if (!$result['success']) {
            return $this->error(
                $result['message'] ?? 'Unable to submit offer',
                $result['code'] ?? 400,
                $result['errors'] ?? null
            );
        }

        $statusCode = !empty($result['created']) ? 201 : 200;

        return $this->success(
            $result['offer'],
            $result['message'] ?? 'Offer submitted successfully',
            $statusCode
        );
    }
}