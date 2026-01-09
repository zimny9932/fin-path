<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\BudgetRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProviderInterface<object>
 */
final readonly class BudgetProvider implements ProviderInterface
{
    public function __construct(
        private BudgetRepository $budgetRepository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $year = $uriVariables['year'];
        $month = $uriVariables['month'];

        if ($year < 2000 || $year > 2100) {
            throw new UnprocessableEntityHttpException('Year must be between 2000 and 2100.');
        }

        if ($month < 1 || $month > 12) {
            throw new UnprocessableEntityHttpException('Month must be between 1 and 12.');
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $budget = $this->budgetRepository->findWithRelations($user, (int)$year, (int)$month);

        if (!$budget) {
            throw new NotFoundHttpException('Budget not found');
        }

        return $budget;
    }
}
