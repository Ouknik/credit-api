<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="API Endpoints for reports and dashboard"
 * )
 */
class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * @OA\Get(
     *     path="/dashboard",
     *     summary="Get dashboard summary",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="balance", type="number"),
     *                 @OA\Property(property="total_customers", type="integer"),
     *                 @OA\Property(property="total_debt", type="number"),
     *                 @OA\Property(property="today_recharges_count", type="integer"),
     *                 @OA\Property(property="today_recharges_amount", type="number")
     *             )
     *         )
     *     )
     * )
     */
    public function dashboard(): JsonResponse
    {
        $data = $this->reportService->getDashboard($this->shopId());

        return $this->success($data);
    }

    /**
     * @OA\Get(
     *     path="/reports/daily",
     *     summary="Get daily report",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="date", in="query", description="Date (YYYY-MM-DD), defaults to today", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Daily report retrieved successfully")
     * )
     */
    public function daily(Request $request): JsonResponse
    {
        $data = $this->reportService->getDailyReport(
            $this->shopId(),
            $request->input('date')
        );

        return $this->success($data);
    }

    /**
     * @OA\Get(
     *     path="/reports/monthly",
     *     summary="Get monthly report",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="year", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="month", in="query", @OA\Schema(type="integer", minimum=1, maximum=12)),
     *     @OA\Response(response=200, description="Monthly report retrieved successfully")
     * )
     */
    public function monthly(Request $request): JsonResponse
    {
        $data = $this->reportService->getMonthlyReport(
            $this->shopId(),
            $request->input('year'),
            $request->input('month')
        );

        return $this->success($data);
    }
}
