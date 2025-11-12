<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class BillingCycleOutput
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
    ) {
    }
}

