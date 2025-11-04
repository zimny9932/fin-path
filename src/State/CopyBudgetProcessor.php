<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\CopyBudgetInput;
use App\Entity\Budget;
use App\Entity\BudgetLimit;
use App\Entity\User;
use App\Exception\BudgetAlreadyExistsException;
use App\Repository\BudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<CopyBudgetInput, Budget>
 */
final class CopyBudgetProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly BudgetRepository $budgetRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Budget
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $targetYear = (int) $uriVariables['year'];
        $targetMonth = (int) $uriVariables['month'];

        $sourceBudget = $this->budgetRepository->findWithRelations(
            $user,
            $data->sourceYear,
            $data->sourceMonth
        );

        if (null === $sourceBudget) {
            throw new NotFoundHttpException('Source budget to copy from does not exist.');
        }

        if (null !== $this->budgetRepository->findOneBy(['user' => $user, 'year' => $targetYear, 'month' => $targetMonth])) {
            throw new BudgetAlreadyExistsException();
        }

        $newBudget = new Budget($user, $targetYear, $targetMonth, $sourceBudget->getPlannedIncome());

        foreach ($sourceBudget->getBudgetLimits() as $sourceLimit) {
            $newLimit = new BudgetLimit($newBudget, $sourceLimit->getSubcategory(), $sourceLimit->getLimitAmount());
            $newBudget->addBudgetLimit($newLimit);
            $this->entityManager->persist($newLimit);
        }
        
        $this->entityManager->persist($newBudget);
        $this->entityManager->flush();

        return $newBudget;
    }
}
