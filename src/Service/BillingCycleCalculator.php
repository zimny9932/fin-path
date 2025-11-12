<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\BillingCycleOutput;
use App\Entity\User;
use DateInterval;
use DateTimeImmutable;

final readonly class BillingCycleCalculator
{
    private const DEFAULT_BILLING_CYCLE_START_DAY = 1;

    public function calculate(User $user, DateTimeImmutable $currentDate): BillingCycleOutput
    {
        $billingCycleStartDay = $user->getBillingCycleStartDay() ?? self::DEFAULT_BILLING_CYCLE_START_DAY;
        $currentDay = (int) $currentDate->format('d');

        if ($currentDay >= $billingCycleStartDay) {
            $startDate = $currentDate->setDate(
                (int) $currentDate->format('Y'),
                (int) $currentDate->format('m'),
                $billingCycleStartDay
            );
        } else {
            $startDate = $currentDate->modify('-1 month')->setDate(
                (int) $currentDate->modify('-1 month')->format('Y'),
                (int) $currentDate->modify('-1 month')->format('m'),
                $billingCycleStartDay
            );
        }

        $endDate = $startDate->modify('+1 month')->sub(new DateInterval('P1D'));

        return new BillingCycleOutput($startDate, $endDate);
    }
}
