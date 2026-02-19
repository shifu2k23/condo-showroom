<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsSnapshot;
use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\ViewingRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class AnalyticsSnapshotService
{
    public function computeAndStore(string $periodType, CarbonInterface|string|null $referenceDate = null): AnalyticsSnapshot
    {
        $normalizedPeriodType = $this->normalizePeriodType($periodType);
        $resolvedReferenceDate = $this->resolveReferenceDate($referenceDate);
        [$periodStart, $periodEnd] = $this->resolvePeriodRange($normalizedPeriodType, $resolvedReferenceDate);

        $metrics = $this->computeMetrics($periodStart, $periodEnd);

        return AnalyticsSnapshot::query()->updateOrCreate(
            [
                'period_type' => $normalizedPeriodType,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'metrics' => $metrics,
            ]
        );
    }

    public function getOrCreateSnapshot(string $periodType, CarbonInterface|string|null $referenceDate = null): AnalyticsSnapshot
    {
        $normalizedPeriodType = $this->normalizePeriodType($periodType);
        $resolvedReferenceDate = $this->resolveReferenceDate($referenceDate);
        [$periodStart, $periodEnd] = $this->resolvePeriodRange($normalizedPeriodType, $resolvedReferenceDate);

        $existingSnapshot = AnalyticsSnapshot::query()
            ->where('period_type', $normalizedPeriodType)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->first();

        return $existingSnapshot ?? $this->computeAndStore($normalizedPeriodType, $resolvedReferenceDate);
    }

    /**
     * @return Collection<int, AnalyticsSnapshot>
     */
    public function recentSnapshots(string $periodType, int $limit = 8): Collection
    {
        $normalizedPeriodType = $this->normalizePeriodType($periodType);

        return AnalyticsSnapshot::query()
            ->where('period_type', $normalizedPeriodType)
            ->orderByDesc('period_start')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function computeMetrics(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $rangeStart = $periodStart->startOfDay();
        $rangeEndExclusive = $periodEnd->addDay()->startOfDay();
        $periodDays = $rangeStart->diffInDays($rangeEndExclusive);

        $viewingRequestsQuery = ViewingRequest::query()
            ->where('requested_start_at', '>=', $rangeStart)
            ->where('requested_start_at', '<', $rangeEndExclusive);

        $viewingTotal = (clone $viewingRequestsQuery)->count();
        $viewingConfirmed = (clone $viewingRequestsQuery)
            ->where('status', ViewingRequest::STATUS_CONFIRMED)
            ->count();
        $viewingCancelled = (clone $viewingRequestsQuery)
            ->where('status', ViewingRequest::STATUS_CANCELLED)
            ->count();

        $viewingConversionRate = $viewingTotal > 0
            ? round(($viewingConfirmed / $viewingTotal) * 100, 2)
            : 0.0;

        $rentals = Rental::query()
            ->with(['unit:id,name,nightly_price_php,monthly_price_php'])
            ->where('status', Rental::STATUS_ACTIVE)
            ->where('starts_at', '<', $rangeEndExclusive)
            ->where('ends_at', '>', $rangeStart)
            ->get();

        $totalUnits = Unit::query()->count();
        $totalRentalNights = 0;
        $totalRevenueEstimate = 0.0;
        $unitPerformance = [];

        foreach ($rentals as $rental) {
            if (! $rental->unit) {
                continue;
            }

            $overlapNights = $this->calculateOverlapNights(
                $rental->starts_at,
                $rental->ends_at,
                $rangeStart,
                $rangeEndExclusive
            );

            if ($overlapNights <= 0) {
                continue;
            }

            $nightlyRate = $this->resolveNightlyRate($rental->unit);
            $rentalRevenue = $overlapNights * $nightlyRate;
            $unitId = (int) $rental->unit->id;

            $totalRentalNights += $overlapNights;
            $totalRevenueEstimate += $rentalRevenue;

            if (! isset($unitPerformance[$unitId])) {
                $unitPerformance[$unitId] = [
                    'unit_id' => $unitId,
                    'unit_name' => $rental->unit->name,
                    'nights' => 0,
                    'revenue_estimate' => 0.0,
                ];
            }

            $unitPerformance[$unitId]['nights'] += $overlapNights;
            $unitPerformance[$unitId]['revenue_estimate'] += $rentalRevenue;
        }

        $occupancyDenominator = $periodDays * $totalUnits;
        $occupancyRate = $occupancyDenominator > 0
            ? round(($totalRentalNights / $occupancyDenominator) * 100, 2)
            : 0.0;

        $unitRows = collect($unitPerformance)
            ->map(function (array $row) use ($periodDays): array {
                $occupancyRate = $periodDays > 0
                    ? round(($row['nights'] / $periodDays) * 100, 2)
                    : 0.0;

                return [
                    'unit_id' => $row['unit_id'],
                    'unit_name' => $row['unit_name'],
                    'nights' => (int) $row['nights'],
                    'revenue_estimate' => round((float) $row['revenue_estimate'], 2),
                    'occupancy_rate' => $occupancyRate,
                ];
            })
            ->values();

        $tickets = MaintenanceTicket::query()
            ->where('created_at', '>=', $rangeStart)
            ->where('created_at', '<', $rangeEndExclusive)
            ->get(['status', 'created_at', 'updated_at']);

        $openStatuses = [
            MaintenanceTicket::STATUS_OPEN,
            MaintenanceTicket::STATUS_IN_PROGRESS,
        ];
        $closedStatuses = [
            MaintenanceTicket::STATUS_RESOLVED,
            MaintenanceTicket::STATUS_CLOSED,
        ];

        $openTickets = $tickets->whereIn('status', $openStatuses);
        $closedTickets = $tickets->whereIn('status', $closedStatuses);
        $closedCount = $closedTickets->count();

        $averageResolutionHours = $closedCount > 0
            ? round((float) $closedTickets->avg(function (MaintenanceTicket $ticket): float {
                $createdAt = $ticket->created_at;
                $updatedAt = $ticket->updated_at ?? $ticket->created_at;

                if (! $createdAt || ! $updatedAt) {
                    return 0.0;
                }

                $seconds = max(0, $createdAt->diffInSeconds($updatedAt, false));

                return $seconds / 3600;
            }), 2)
            : 0.0;

        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'period_days' => $periodDays,
            ],
            'viewing_requests' => [
                'total' => $viewingTotal,
                'confirmed' => $viewingConfirmed,
                'cancelled' => $viewingCancelled,
                'conversion_rate' => $viewingConversionRate,
            ],
            'rentals' => [
                'nights' => $totalRentalNights,
                'occupancy_rate' => $occupancyRate,
                'revenue_estimate' => round($totalRevenueEstimate, 2),
            ],
            'tickets' => [
                'open' => $openTickets->count(),
                'closed' => $closedCount,
                'avg_resolution_hours' => $averageResolutionHours,
            ],
            'top_units_by_revenue' => $unitRows
                ->sortByDesc('revenue_estimate')
                ->take(5)
                ->values()
                ->all(),
            'top_units_by_occupancy' => $unitRows
                ->sortByDesc('occupancy_rate')
                ->take(5)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolvePeriodRange(string $periodType, CarbonImmutable $referenceDate): array
    {
        return match ($periodType) {
            AnalyticsSnapshot::PERIOD_DAY => [
                $referenceDate->startOfDay(),
                $referenceDate->endOfDay(),
            ],
            AnalyticsSnapshot::PERIOD_WEEK => [
                $referenceDate->startOfWeek()->startOfDay(),
                $referenceDate->endOfWeek()->endOfDay(),
            ],
            AnalyticsSnapshot::PERIOD_MONTH => [
                $referenceDate->startOfMonth()->startOfDay(),
                $referenceDate->endOfMonth()->endOfDay(),
            ],
            default => throw new InvalidArgumentException('Unsupported period type: '.$periodType),
        };
    }

    private function normalizePeriodType(string $periodType): string
    {
        $normalized = strtoupper(trim($periodType));

        if (! in_array($normalized, [
            AnalyticsSnapshot::PERIOD_DAY,
            AnalyticsSnapshot::PERIOD_WEEK,
            AnalyticsSnapshot::PERIOD_MONTH,
        ], true)) {
            throw new InvalidArgumentException('Unsupported period type: '.$periodType);
        }

        return $normalized;
    }

    private function resolveReferenceDate(CarbonInterface|string|null $referenceDate): CarbonImmutable
    {
        if ($referenceDate instanceof CarbonImmutable) {
            return $referenceDate;
        }

        if ($referenceDate instanceof CarbonInterface) {
            return CarbonImmutable::instance($referenceDate);
        }

        if (is_string($referenceDate) && trim($referenceDate) !== '') {
            return CarbonImmutable::parse($referenceDate);
        }

        return CarbonImmutable::now();
    }

    private function calculateOverlapNights(
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEndExclusive
    ): int {
        $bookingStart = $startsAt instanceof CarbonImmutable
            ? $startsAt->startOfDay()
            : CarbonImmutable::instance($startsAt)->startOfDay();

        $bookingEnd = $endsAt instanceof CarbonImmutable
            ? $endsAt->startOfDay()
            : CarbonImmutable::instance($endsAt)->startOfDay();

        $overlapStart = $bookingStart->greaterThan($windowStart) ? $bookingStart : $windowStart;
        $overlapEnd = $bookingEnd->lessThan($windowEndExclusive) ? $bookingEnd : $windowEndExclusive;

        if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
            return 0;
        }

        return $overlapStart->diffInDays($overlapEnd);
    }

    private function resolveNightlyRate(Unit $unit): float
    {
        if ($unit->nightly_price_php !== null) {
            return (float) $unit->nightly_price_php;
        }

        if ($unit->monthly_price_php !== null) {
            return round(((float) $unit->monthly_price_php) / 30, 2);
        }

        return 0.0;
    }
}

