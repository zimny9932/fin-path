<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class MoneyOutput
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {
    }
}
