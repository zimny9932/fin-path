<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\BudgetProgressOutput;
use App\DTO\DashboardOutput;
use App\DTO\DashboardSummaryOutput;
use App\DTO\MoneyOutput;
use App\Entity\User;
use App\Exception\OnboardingNotCompletedException;
use App\Model\ValueObject\Money;
use App\Repository\BudgetLimitRepository;
use App\Repository\BudgetRepository;
use App\Repository\TransactionRepository;
use App\Service\BillingCycleCalculator;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<DashboardOutput>
 */
final readonly class DashboardProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private BillingCycleCalculator $billingCycleCalculator,
        private TransactionRepository $transactionRepository,
        private BudgetRepository $budgetRepository,
        private BudgetLimitRepository $budgetLimitRepository
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DashboardOutput
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $currentDate = new DateTimeImmutable();
        $billingCycle = $this->billingCycleCalculator->calculate($user, $currentDate);

        $totals = $this->transactionRepository->getTotalsInDateRange($user, $billingCycle->startDate, $billingCycle->endDate);
        $balance = Money::fromMoney($totals->totalIncome)->subtract($totals->totalExpenses);

        $summary = new DashboardSummaryOutput(
            MoneyOutput::fromMoney($totals->totalIncome),
            MoneyOutput::fromMoney($totals->totalExpenses),
            MoneyOutput::fromMoney($balance)
        );

        $budget = $this->budgetRepository->findOneBy([
            'user' => $user,
            'year' => (int) $currentDate->format('Y'),
            'month' => (int) $currentDate->format('m'),
        ]);

        $budgetProgress = null;
        if (null !== $budget) {
            $plannedExpenses = $this->budgetLimitRepository->getTotalPlannedExpenses($budget);
            $percentage = $plannedExpenses->getAmount() > 0 ? (int) (($totals->totalExpenses->getAmount() / $plannedExpenses->getAmount()) * 100) : 0;
            $budgetProgress = new BudgetProgressOutput(
                MoneyOutput::fromMoney($plannedExpenses),
                MoneyOutput::fromMoney($totals->totalExpenses),
                $percentage
            );
        }

        return new DashboardOutput($billingCycle, $summary, $budgetProgress);
    }
}
