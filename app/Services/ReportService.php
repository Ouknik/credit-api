<?php

namespace App\Services;

use App\Repositories\ShopRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\DebtRepository;
use App\Repositories\RechargeRepository;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        private ShopRepository $shopRepository,
        private CustomerRepository $customerRepository,
        private DebtRepository $debtRepository,
        private RechargeRepository $rechargeRepository
    ) {}

    public function getDashboard(string $shopId): array
    {
        $shop = $this->shopRepository->find($shopId);
        $today = Carbon::today();

        return [
            'balance' => $shop->balance,
            'total_customers' => $this->customerRepository->countByShopId($shopId),
            'total_debt' => $this->customerRepository->getTotalDebtByShopId($shopId),
            'today_recharges_count' => $this->rechargeRepository->getTodayRechargesCountByShopId($shopId),
            'today_recharges_amount' => $this->rechargeRepository->getTodayRechargesAmountByShopId($shopId),
        ];
    }

    public function getDailyReport(string $shopId, ?string $date = null): array
    {
        $reportDate = $date ? Carbon::parse($date) : Carbon::today();
        $startOfDay = $reportDate->copy()->startOfDay();
        $endOfDay = $reportDate->copy()->endOfDay();

        $recharges = $this->rechargeRepository->getDailyRechargesByShopId($shopId, $reportDate);
        
        $successfulRecharges = $recharges->where('status', 'success');
        $failedRecharges = $recharges->where('status', 'failed');
        $pendingRecharges = $recharges->where('status', 'pending');

        return [
            'date' => $reportDate->toDateString(),
            'recharges' => [
                'total_count' => $recharges->count(),
                'successful_count' => $successfulRecharges->count(),
                'failed_count' => $failedRecharges->count(),
                'pending_count' => $pendingRecharges->count(),
                'successful_amount' => $successfulRecharges->sum('amount'),
                'by_operator' => $successfulRecharges->groupBy('operator')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount'),
                    ];
                }),
            ],
            'debts' => [
                'total_new_debt' => $this->debtRepository->getDailyDebtsByShopId($shopId, $reportDate),
            ],
        ];
    }

    public function getMonthlyReport(string $shopId, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;
        
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $recharges = $this->rechargeRepository->getMonthlyRechargesByShopId($shopId, $year, $month);

        $successfulRecharges = $recharges->where('status', 'success');
        $failedRecharges = $recharges->where('status', 'failed');

        // Daily breakdown
        $dailyBreakdown = [];
        $currentDate = $startOfMonth->copy();
        
        while ($currentDate <= $endOfMonth) {
            $dayRecharges = $recharges->filter(function ($r) use ($currentDate) {
                return Carbon::parse($r->created_at)->toDateString() === $currentDate->toDateString();
            });
            
            $successDayRecharges = $dayRecharges->where('status', 'success');
            
            $dailyBreakdown[$currentDate->toDateString()] = [
                'count' => $dayRecharges->count(),
                'successful_count' => $successDayRecharges->count(),
                'amount' => $successDayRecharges->sum('amount'),
            ];
            
            $currentDate->addDay();
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => Carbon::create($year, $month, 1)->format('F'),
            'summary' => [
                'total_recharges' => $recharges->count(),
                'successful_recharges' => $successfulRecharges->count(),
                'failed_recharges' => $failedRecharges->count(),
                'total_amount' => $successfulRecharges->sum('amount'),
                'average_per_day' => round($successfulRecharges->sum('amount') / $endOfMonth->day, 2),
            ],
            'by_operator' => $successfulRecharges->groupBy('operator')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
            'daily_breakdown' => $dailyBreakdown,
            'total_debt' => $this->debtRepository->getMonthlyDebtsByShopId($shopId, $year, $month),
        ];
    }
}
