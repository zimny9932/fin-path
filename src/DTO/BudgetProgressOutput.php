<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class BudgetProgressOutput
{
    public function __construct(
        public MoneyOutput $planned,
        public MoneyOutput $spent,
        #[Assert\Range(min: 0, max: 100)]
        public int $percentage,
    ) {
    }
}

