<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SpendingByCategoryOutput
{
    public function __construct(
        public string $mainCategory,
        public MoneyOutput $totalAmount,
        public float $percentageOfTotal,
    ) {
    }
}
