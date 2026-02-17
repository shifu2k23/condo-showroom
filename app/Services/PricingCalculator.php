<?php

namespace App\Services;

use App\Models\Unit;
use Carbon\CarbonInterface;

class PricingCalculator
{
    public function calculate(Unit $unit, CarbonInterface $start, CarbonInterface $end): array
    {
        if ($start->gte($end)) {
            return [
                'total' => 0,
                'nights' => 0,
                'breakdown' => 'Invalid dates',
                'nightly_rate_used' => null,
                'months_applied' => 0,
            ];
        }

        $nights = (int) $start->diffInDays($end);
        $nightly = $this->resolvedNightlyRate($unit);
        $monthly = $unit->monthly_price_php;

        if ($nights === 0 || ($nightly === null && $monthly === null)) {
            return [
                'total' => 0,
                'nights' => $nights,
                'breakdown' => 'Pricing unavailable',
                'nightly_rate_used' => $nightly,
                'months_applied' => 0,
            ];
        }

        $mode = $unit->estimator_mode ?? Unit::ESTIMATOR_HYBRID;

        if ($mode === Unit::ESTIMATOR_NIGHTLY_ONLY) {
            return $this->nightlyOnly($nights, $nightly);
        }

        if ($mode === Unit::ESTIMATOR_MONTHLY_ONLY) {
            return $this->monthlyOnly($nights, $monthly, $nightly);
        }

        return $this->hybrid($nights, $monthly, $nightly);
    }

    public function formatPhp(int $amount): string
    {
        return 'PHP '.number_format($amount, 0, '.', ',');
    }

    private function resolvedNightlyRate(Unit $unit): ?int
    {
        if ($unit->nightly_price_php !== null) {
            return (int) $unit->nightly_price_php;
        }

        if ($unit->monthly_price_php !== null) {
            return (int) round($unit->monthly_price_php / 30);
        }

        return null;
    }

    private function nightlyOnly(int $nights, ?int $nightly): array
    {
        if ($nightly === null) {
            return [
                'total' => 0,
                'nights' => $nights,
                'breakdown' => 'Nightly pricing unavailable',
                'nightly_rate_used' => null,
                'months_applied' => 0,
            ];
        }

        $total = $nights * $nightly;

        return [
            'total' => $total,
            'nights' => $nights,
            'breakdown' => "{$nights} night(s) x ".$this->formatPhp($nightly),
            'nightly_rate_used' => $nightly,
            'months_applied' => 0,
        ];
    }

    private function monthlyOnly(int $nights, ?int $monthly, ?int $nightlyFallback): array
    {
        $nightly = $monthly !== null
            ? (int) round($monthly / 30)
            : $nightlyFallback;

        if ($nightly === null) {
            return [
                'total' => 0,
                'nights' => $nights,
                'breakdown' => 'Monthly pricing unavailable',
                'nightly_rate_used' => null,
                'months_applied' => 0,
            ];
        }

        $total = $nights * $nightly;

        return [
            'total' => $total,
            'nights' => $nights,
            'breakdown' => "{$nights} night(s) monthly-prorated x ".$this->formatPhp($nightly),
            'nightly_rate_used' => $nightly,
            'months_applied' => 0,
        ];
    }

    private function hybrid(int $nights, ?int $monthly, ?int $nightly): array
    {
        if ($nightly === null) {
            return [
                'total' => 0,
                'nights' => $nights,
                'breakdown' => 'Nightly pricing unavailable',
                'nightly_rate_used' => null,
                'months_applied' => 0,
            ];
        }

        if ($monthly !== null && $nights >= 30) {
            $months = intdiv($nights, 30);
            $remainder = $nights % 30;
            $total = ($months * $monthly) + ($remainder * $nightly);

            $parts = ["{$months} month(s) x ".$this->formatPhp($monthly)];
            if ($remainder > 0) {
                $parts[] = "{$remainder} night(s) x ".$this->formatPhp($nightly);
            }

            return [
                'total' => $total,
                'nights' => $nights,
                'breakdown' => implode(' + ', $parts),
                'nightly_rate_used' => $nightly,
                'months_applied' => $months,
            ];
        }

        $total = $nights * $nightly;

        return [
            'total' => $total,
            'nights' => $nights,
            'breakdown' => "{$nights} night(s) x ".$this->formatPhp($nightly),
            'nightly_rate_used' => $nightly,
            'months_applied' => 0,
        ];
    }
}
