<?php

namespace App\Console\Commands;

use App\Models\AnalyticsSnapshot;
use App\Services\Analytics\AnalyticsSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ComputeAnalyticsSnapshotsCommand extends Command
{
    protected $signature = 'analytics:snapshot
        {--period=all : day|week|month|all}
        {--date= : Reference date (Y-m-d)}';

    protected $description = 'Compute and store analytics snapshots for admin reporting';

    public function handle(AnalyticsSnapshotService $snapshotService): int
    {
        try {
            $referenceDate = $this->resolveReferenceDate();
            $periods = $this->resolvePeriods();

            foreach ($periods as $periodType) {
                $snapshot = $snapshotService->computeAndStore($periodType, $referenceDate);

                $this->info(sprintf(
                    'Computed %s snapshot for %s to %s.',
                    strtolower($snapshot->period_type),
                    $snapshot->period_start->toDateString(),
                    $snapshot->period_end->toDateString(),
                ));
            }

            return self::SUCCESS;
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::INVALID;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Failed to compute analytics snapshots. Please check the logs and try again.');

            return self::FAILURE;
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolvePeriods(): array
    {
        $periodOption = strtolower((string) $this->option('period'));

        return match ($periodOption) {
            'day' => [AnalyticsSnapshot::PERIOD_DAY],
            'week' => [AnalyticsSnapshot::PERIOD_WEEK],
            'month' => [AnalyticsSnapshot::PERIOD_MONTH],
            'all' => [AnalyticsSnapshot::PERIOD_WEEK, AnalyticsSnapshot::PERIOD_MONTH],
            default => throw new InvalidArgumentException('Invalid period. Use day, week, month, or all.'),
        };
    }

    private function resolveReferenceDate(): CarbonImmutable
    {
        $dateOption = (string) $this->option('date');

        if ($dateOption === '') {
            return CarbonImmutable::now();
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $dateOption)
            ?: throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
    }
}

