<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RechargeService;
use App\Http\Requests\InitiateRechargeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Recharges",
 *     description="API Endpoints for recharge operations"
 * )
 */
class RechargeController extends Controller
{
    public function __construct(
        private RechargeService $rechargeService
    ) {}

    /**
     * @OA\Get(
     *     path="/recharges",
     *     summary="Get all recharges",
     *     tags={"Recharges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending", "success", "failed"})),
     *     @OA\Parameter(name="operator", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="phone", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Recharges retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $recharges = $this->rechargeService->getRechargesByShop(
            $this->shopId(),
            $request->input('per_page', 15),
            $request->only(['status', 'operator', 'phone', 'customer_id', 'date_from', 'date_to'])
        );

        return $this->success($recharges);
    }

    /**
     * @OA\Post(
     *     path="/recharges",
     *     summary="Initiate a new recharge",
     *     tags={"Recharges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone", "operator", "amount"},
     *             @OA\Property(property="phone", type="string", example="+212600000000"),
     *             @OA\Property(property="operator", type="string", example="maroc_telecom"),
     *             @OA\Property(property="amount", type="number", example=10.00),
     *             @OA\Property(property="customer_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="idempotency_key", type="string", description="Unique key to prevent duplicate recharges")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Recharge initiated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recharge", ref="#/components/schemas/Recharge"),
     *                 @OA\Property(property="duplicate", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Insufficient balance or shop suspended"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(InitiateRechargeRequest $request): JsonResponse
    {
        $result = $this->rechargeService->initiateRecharge(
            $this->shopId(),
            $request->validated()
        );

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        $statusCode = isset($result['duplicate']) && $result['duplicate'] ? 200 : 201;

        return $this->success([
            'recharge' => $result['recharge'],
            'duplicate' => $result['duplicate'] ?? false,
        ], $result['message'], $statusCode);
    }

    /**
     * @OA\Get(
     *     path="/recharges/{id}",
     *     summary="Get a single recharge by ID",
     *     tags={"Recharges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Recharge retrieved successfully"),
     *     @OA\Response(response=404, description="Recharge not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $recharge = \App\Models\Recharge::where('id', $id)
            ->where('shop_id', $this->shopId())
            ->with('customer')
            ->first();

        if (!$recharge) {
            return $this->error('Recharge not found', 404);
        }

        return $this->success($recharge);
    }

    /**
     * Check if the recharge gateway (Pi) is online and healthy.
     */
    public function gatewayHealth(): JsonResponse
    {
        $gateway = app(\App\Services\CadeauxGateway::class);
        $health = $gateway->checkHealth();

        return $this->success($health);
    }

    /**
     * @OA\Get(
     *     path="/recharges/operators",
     *     summary="Get available operators",
     *     tags={"Recharges"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Operators retrieved successfully")
     * )
     */
    public function operators(): JsonResponse
    {
        $operators = [
            [
                'id'     => 'maroc_telecom',
                'name'   => 'Maroc Telecom',
                'offers' => [
                    ['offer' => '1', 'amount' => 5,   'label' => '5 DH'],
                    ['offer' => '2', 'amount' => 10,  'label' => '10 DH'],
                    ['offer' => '3', 'amount' => 20,  'label' => '20 DH'],
                    ['offer' => '4', 'amount' => 50,  'label' => '50 DH'],
                    ['offer' => '5', 'amount' => 100, 'label' => '100 DH'],
                    ['offer' => '6', 'amount' => 200, 'label' => '200 DH'],
                ],
            ],
            [
                'id'     => 'inwi',
                'name'   => 'Inwi',
                'offers' => [
                    ['offer' => '1', 'amount' => 5,   'label' => '5 DH'],
                    ['offer' => '2', 'amount' => 10,  'label' => '10 DH'],
                    ['offer' => '3', 'amount' => 20,  'label' => '20 DH'],
                    ['offer' => '4', 'amount' => 50,  'label' => '50 DH'],
                    ['offer' => '5', 'amount' => 100, 'label' => '100 DH'],
                    ['offer' => '6', 'amount' => 200, 'label' => '200 DH'],
                ],
            ],
            [
                'id'     => 'orange',
                'name'   => 'Orange',
                'offers' => [
                    ['offer' => '1', 'amount' => 5,   'label' => '5 DH'],
                    ['offer' => '2', 'amount' => 10,  'label' => '10 DH'],
                    ['offer' => '3', 'amount' => 20,  'label' => '20 DH'],
                    ['offer' => '4', 'amount' => 50,  'label' => '50 DH'],
                    ['offer' => '5', 'amount' => 100, 'label' => '100 DH'],
                    ['offer' => '6', 'amount' => 200, 'label' => '200 DH'],
                ],
            ],
        ];

        return $this->success($operators);
    }
}
