<?php

use App\Models\Unit;
use App\Services\PricingCalculator;
use Carbon\CarbonImmutable;

test('pricing calculator computes nightly only', function () {
    $unit = Unit::make([
        'nightly_price_php' => 1500,
        'monthly_price_php' => null,
        'estimator_mode' => Unit::ESTIMATOR_HYBRID,
    ]);

    $calculator = new PricingCalculator;
    $result = $calculator->calculate(
        $unit,
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-06')
    );

    expect($result['nights'])->toBe(5);
    expect($result['total'])->toBe(7500);
});

test('pricing calculator derives nightly from monthly when nightly missing', function () {
    $unit = Unit::make([
        'nightly_price_php' => null,
        'monthly_price_php' => 30000,
        'estimator_mode' => Unit::ESTIMATOR_HYBRID,
    ]);

    $calculator = new PricingCalculator;
    $result = $calculator->calculate(
        $unit,
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-11')
    );

    expect($result['nightly_rate_used'])->toBe(1000);
    expect($result['total'])->toBe(10000);
});

test('pricing calculator applies hybrid monthly plus remainder', function () {
    $unit = Unit::make([
        'nightly_price_php' => 1200,
        'monthly_price_php' => 30000,
        'estimator_mode' => Unit::ESTIMATOR_HYBRID,
    ]);

    $calculator = new PricingCalculator;
    $result = $calculator->calculate(
        $unit,
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-04-10')
    );

    expect($result['nights'])->toBe(40);
    expect($result['months_applied'])->toBe(1);
    expect($result['total'])->toBe(42000);
});
