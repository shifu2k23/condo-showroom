<?php

namespace App\Application\Viewing\Data;

use Carbon\CarbonImmutable;

final readonly class CreateViewingRequestData
{
    public function __construct(
        public int $unitId,
        public CarbonImmutable $requestedStartAt,
        public ?CarbonImmutable $requestedEndAt,
        public string $requesterName,
        public ?string $requesterPhone = null,
        public ?string $requesterEmail = null,
        public ?string $notes = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
