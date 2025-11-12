<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class DashboardOutput
{
    public function __construct(
        public BillingCycleOutput $billingCycle,
        public DashboardSummaryOutput $summary,
        public ?BudgetProgressOutput $budgetProgress = null,
    ) {
    }
}

