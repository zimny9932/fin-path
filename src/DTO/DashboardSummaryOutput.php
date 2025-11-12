<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class DashboardSummaryOutput
{
    public function __construct(
        public MoneyOutput $totalIncome,
        public MoneyOutput $totalExpenses,
        public MoneyOutput $balance,
    ) {
    }
}

