<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DebtService;
use App\Http\Requests\AddDebtRequest;
use App\Http\Requests\RegisterPaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Debts",
 *     description="API Endpoints for debt management"
 * )
 */
class DebtController extends Controller
{
    public function __construct(
        private DebtService $debtService
    ) {}

    /**
     * @OA\Get(
     *     path="/debts",
     *     summary="Get all debts",
     *     tags={"Debts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="customer_id", in="query", @OA\Schema(type="string", format="uuid")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"manual", "recharge", "payment"})),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Debts retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $debts = $this->debtService->getDebtsByShop(
            $this->shopId(),
            $request->input('per_page', 15),
            $request->only(['customer_id', 'type', 'date_from', 'date_to'])
        );

        return $this->success($debts);
    }

    /**
     * @OA\Get(
     *     path="/customers/{customerId}/debts",
     *     summary="Get debts for a specific customer",
     *     tags={"Debts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer debts retrieved successfully")
     * )
     */
    public function customerDebts(string $customerId): JsonResponse
    {
        $debts = $this->debtService->getDebtsByCustomer($this->shopId(), $customerId);

        return $this->success($debts);
    }

    /**
     * @OA\Post(
     *     path="/customers/{customerId}/debts",
     *     summary="Add debt to customer",
     *     tags={"Debts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example=50.00),
     *             @OA\Property(property="type", type="string", enum={"manual", "recharge"}, example="manual"),
     *             @OA\Property(property="description", type="string", example="Credit for groceries")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Debt added successfully"),
     *     @OA\Response(response=400, description="Customer has reached maximum debt limit"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function addDebt(AddDebtRequest $request, string $customerId): JsonResponse
    {
        try {
            $debt = $this->debtService->addDebt(
                $this->shopId(),
                $customerId,
                $request->validated()
            );

            if (!$debt) {
                return $this->error('Customer not found', 404);
            }

            return $this->success($debt, 'Debt added successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/customers/{customerId}/payments",
     *     summary="Register payment from customer",
     *     tags={"Debts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="customerId", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example=100.00),
     *             @OA\Property(property="description", type="string", example="Payment received")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payment registered successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function registerPayment(RegisterPaymentRequest $request, string $customerId): JsonResponse
    {
        $debt = $this->debtService->registerPayment(
            $this->shopId(),
            $customerId,
            $request->validated()
        );

        if (!$debt) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($debt, 'Payment registered successfully', 201);
    }
}
