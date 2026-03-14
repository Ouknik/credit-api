<?php

namespace App\Console\Commands;

use App\Models\Recharge;
use App\Services\RechargeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net: finds recharges stuck in non-terminal states for too long
 * and refunds the shop balance.
 *
 * This handles edge cases where:
 * - The Pi crashed mid-recharge and never sent a callback
 * - The callback HTTP request failed after all retries
 * - Laravel crashed between deducting balance and delivering to Pi
 *
 * Schedule: runs every 5 minutes via Laravel scheduler.
 */
class SweepStuckRecharges extends Command
{
    protected $signature = 'recharges:sweep
                            {--minutes=10 : Consider recharges stuck after this many minutes}
                            {--dry-run : Show what would be refunded without actually doing it}';

    protected $description = 'Refund recharges stuck in pending/processing for too long';

    public function handle(RechargeService $rechargeService): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun  = $this->option('dry-run');
        $cutoff  = now()->subMinutes($minutes);

        $stuck = Recharge::whereIn('status', ['pending', 'processing', 'queued'])
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($stuck->isEmpty()) {
            $this->info('No stuck recharges found.');
            return 0;
        }

        $this->warn("Found {$stuck->count()} stuck recharge(s) older than {$minutes} minutes:");

        foreach ($stuck as $recharge) {
            $age = $recharge->updated_at->diffInMinutes(now());

            $this->line(sprintf(
                '  [%s] %s | %s | %s MAD | %s | stuck %d min',
                $recharge->reference_code,
                $recharge->phone,
                $recharge->operator,
                $recharge->amount,
                $recharge->status,
                $age,
            ));

            if ($dryRun) {
                $this->comment('    → Would refund (dry-run)');
                continue;
            }

            try {
                $rechargeService->handleRechargeFailure($recharge, [
                    'success' => false,
                    'error'   => "Stuck recharge swept after {$age} minutes (status: {$recharge->status})",
                    'source'  => 'sweep_command',
                ]);

                $this->info("    → Refunded {$recharge->amount} MAD to shop {$recharge->shop_id}");

                Log::warning('SweepStuckRecharges: refunded stuck recharge', [
                    'recharge_id'    => $recharge->id,
                    'reference_code' => $recharge->reference_code,
                    'status'         => $recharge->status,
                    'age_minutes'    => $age,
                    'amount'         => $recharge->amount,
                    'shop_id'        => $recharge->shop_id,
                ]);
            } catch (\Exception $e) {
                $this->error("    → Failed to refund: {$e->getMessage()}");
                Log::error('SweepStuckRecharges: failed to refund', [
                    'recharge_id' => $recharge->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry-run complete. No changes made."
            : "Sweep complete. {$stuck->count()} recharge(s) refunded."
        );

        return 0;
    }
}
