<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use App\Http\Requests\CreateCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="API Endpoints for customer management"
 * )
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * @OA\Get(
     *     path="/customers",
     *     summary="Get all customers",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", description="Page number", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", description="Search by name or phone", @OA\Schema(type="string")),
     *     @OA\Parameter(name="is_trusted", in="query", description="Filter by trusted status", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="has_debt", in="query", description="Filter customers with debt", @OA\Schema(type="boolean")),
     *     @OA\Response(
     *         response=200,
     *         description="Customers retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerService->getCustomersByShop(
            $this->shopId(),
            $request->input('per_page', 15),
            $request->only(['search', 'is_trusted', 'has_debt'])
        );

        return $this->success($customers);
    }

    /**
     * @OA\Post(
     *     path="/customers",
     *     summary="Create a new customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "phone"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="phone", type="string", example="+212600000000"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="is_trusted", type="boolean", example=false),
     *             @OA\Property(property="daily_limit", type="number", example=100.00),
     *             @OA\Property(property="monthly_limit", type="number", example=1000.00),
     *             @OA\Property(property="max_debt_limit", type="number", example=500.00)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Customer created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer(
            $this->shopId(),
            $request->validated()
        );

        return $this->success($customer, 'Customer created successfully', 201);
    }

    /**
     * @OA\Get(
     *     path="/customers/{id}",
     *     summary="Get customer details",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer retrieved successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $customer = $this->customerService->getCustomer($this->shopId(), $id);

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($customer);
    }

    /**
     * @OA\Put(
     *     path="/customers/{id}",
     *     summary="Update customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="is_trusted", type="boolean"),
     *             @OA\Property(property="daily_limit", type="number"),
     *             @OA\Property(property="monthly_limit", type="number"),
     *             @OA\Property(property="max_debt_limit", type="number")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Customer updated successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $customer = $this->customerService->updateCustomer(
            $this->shopId(),
            $id,
            $request->validated()
        );

        if (!$customer) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($customer, 'Customer updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/customers/{id}",
     *     summary="Delete customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Customer deleted successfully"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->customerService->deleteCustomer($this->shopId(), $id);

        if (!$deleted) {
            return $this->error('Customer not found', 404);
        }

        return $this->success(null, 'Customer deleted successfully');
    }
}
