<?php

namespace App\Domain\Pricing;

enum EstimatorMode: string
{
    case HYBRID = 'HYBRID';
    case NIGHTLY_ONLY = 'NIGHTLY_ONLY';
    case MONTHLY_ONLY = 'MONTHLY_ONLY';
}
