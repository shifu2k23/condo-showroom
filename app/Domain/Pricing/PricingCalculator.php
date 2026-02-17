<?php

namespace App\Domain\Pricing;

use Carbon\CarbonImmutable;
use DomainException;

final class PricingCalculator
{
    public function quote(
        ?int $nightlyPricePhp,
        ?int $monthlyPricePhp,
        EstimatorMode $mode,
        CarbonImmutable $checkIn,
        CarbonImmutable $checkOut,
    ): PriceQuote {
        if ($checkOut->lte($checkIn)) {
            throw new DomainException('Check-out must be after check-in.');
        }

        $nights = (int) $checkIn->diffInDays($checkOut);
        $nightlyRate = $this->resolveNightlyRate($nightlyPricePhp, $monthlyPricePhp);

        if ($mode === EstimatorMode::NIGHTLY_ONLY) {
            $total = $nights * $nightlyRate;

            return new PriceQuote(
                nights: $nights,
                totalPhp: $total,
                nightlyRateUsedPhp: $nightlyRate,
                monthsApplied: 0,
                remainderNights: $nights,
                breakdown: "{$nights} night(s) x ".$this->formatPhp($nightlyRate),
            );
        }

        if ($mode === EstimatorMode::MONTHLY_ONLY) {
            $monthlyRate = $monthlyPricePhp ?? ($nightlyRate * 30);
            $proratedNightly = (int) round($monthlyRate / 30);
            $total = $nights * $proratedNightly;

            return new PriceQuote(
                nights: $nights,
                totalPhp: $total,
                nightlyRateUsedPhp: $proratedNightly,
                monthsApplied: 0,
                remainderNights: $nights,
                breakdown: "{$nights} night(s) monthly-prorated x ".$this->formatPhp($proratedNightly),
            );
        }

        if ($monthlyPricePhp !== null && $nights >= 30) {
            $months = intdiv($nights, 30);
            $remainder = $nights % 30;
            $total = ($months * $monthlyPricePhp) + ($remainder * $nightlyRate);

            $parts = ["{$months} month(s) x ".$this->formatPhp($monthlyPricePhp)];
            if ($remainder > 0) {
                $parts[] = "{$remainder} night(s) x ".$this->formatPhp($nightlyRate);
            }

            return new PriceQuote(
                nights: $nights,
                totalPhp: $total,
                nightlyRateUsedPhp: $nightlyRate,
                monthsApplied: $months,
                remainderNights: $remainder,
                breakdown: implode(' + ', $parts),
            );
        }

        $total = $nights * $nightlyRate;

        return new PriceQuote(
            nights: $nights,
            totalPhp: $total,
            nightlyRateUsedPhp: $nightlyRate,
            monthsApplied: 0,
            remainderNights: $nights,
            breakdown: "{$nights} night(s) x ".$this->formatPhp($nightlyRate),
        );
    }

    public function formatPhp(int $amount): string
    {
        return 'PHP '.number_format($amount, 0, '.', ',');
    }

    private function resolveNightlyRate(?int $nightlyPricePhp, ?int $monthlyPricePhp): int
    {
        if ($nightlyPricePhp !== null) {
            return $nightlyPricePhp;
        }

        if ($monthlyPricePhp !== null) {
            return (int) round($monthlyPricePhp / 30);
        }

        throw new DomainException('At least one price (nightly/monthly) is required.');
    }
}
