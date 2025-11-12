<?php

declare(strict_types=1);

namespace App\DTO;

use App\Model\ValueObject\Money;

final readonly class TransactionTotalsOutput
{
    public function __construct(
        public Money $totalIncome,
        public Money $totalExpenses,
    ) {
    }
}

