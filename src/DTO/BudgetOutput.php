<?php

declare(strict_types=1);

namespace App\DTO;

use App\Model\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

final readonly class BudgetOutput
{
    /**
     * @param BudgetLimitOutput[] $limits
     */
    public function __construct(
        public Uuid $id,
        public int $year,
        public int $month,
        public Money $plannedIncome,
        public array $limits,
    ) {
    }
}


