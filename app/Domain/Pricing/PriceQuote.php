<?php

namespace App\Domain\Pricing;

final readonly class PriceQuote
{
    public function __construct(
        public int $nights,
        public int $totalPhp,
        public int $nightlyRateUsedPhp,
        public int $monthsApplied,
        public int $remainderNights,
        public string $breakdown,
    ) {}
}
