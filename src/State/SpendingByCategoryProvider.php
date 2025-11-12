<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\MoneyOutput;
use App\DTO\SpendingByCategoryOutput;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Service\BillingCycleCalculator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<SpendingByCategoryOutput>
 */
final readonly class SpendingByCategoryProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private BillingCycleCalculator $billingCycleCalculator,
        private TransactionRepository $transactionRepository,
        private RequestStack $requestStack
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        [$startDate, $endDate] = $this->getDateRange($user, $context);

        $spendingData = $this->transactionRepository->findSpendingByCategory($user, $startDate, $endDate);

        if (empty($spendingData)) {
            return [];
        }

        $grandTotal = array_sum(array_column($spendingData, 'total'));

        $result = [];
        foreach ($spendingData as $item) {
            $percentage = $grandTotal > 0 ? round(($item['total'] / $grandTotal) * 100, 2) : 0;
            $result[] = new SpendingByCategoryOutput(
                $item['mainCategory'],
                new MoneyOutput($item['total'], $item['currency']),
                $percentage
            );
        }

        return $result;
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function getDateRange(User $user, array $context): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $startDateStr = $request?->query->get('startDate');
        $endDateStr = $request?->query->get('endDate');

        if (!$startDateStr && !$endDateStr) {
            $billingCycle = $this->billingCycleCalculator->calculate($user, new \DateTimeImmutable());

            return [$billingCycle->startDate, $billingCycle->endDate];
        }

        try {
            $startDate = new \DateTimeImmutable($startDateStr);
            $endDate = new \DateTimeImmutable($endDateStr);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Invalid date format. Please use YYYY-MM-DD.');
        }

        if ($startDate > $endDate) {
            throw new BadRequestHttpException('Start date cannot be after end date.');
        }

        return [$startDate, $endDate];
    }
}
