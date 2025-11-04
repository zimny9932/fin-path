<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\BudgetInput;
use App\Entity\Budget;
use App\Entity\BudgetLimit;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Exception\BudgetAlreadyExistsException;
use App\Model\ValueObject\Money;
use App\Repository\BudgetRepository;
use App\Repository\SubcategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<BudgetInput, Budget>
 */
final readonly class BudgetProcessor implements ProcessorInterface
{
    public function __construct(
        private BudgetRepository $budgetRepository,
        private SubcategoryRepository $subcategoryRepository,
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Budget
    {
        if (!$data instanceof BudgetInput) {
            throw new \InvalidArgumentException('Expected BudgetInput object.');
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if ($operation instanceof Post && !isset($context['previous_data'])) {
            return $this->createBudget($data, $user);
        }

        /** @var Budget $budget */
        $budget = $context['previous_data'];

        return $this->updateBudget($budget, $data, $user);
    }

    private function createBudget(BudgetInput $data, User $user): Budget
    {
        if ($this->budgetRepository->findOneBy(['user' => $user, 'year' => $data->year, 'month' => $data->month])) {
            throw new BudgetAlreadyExistsException();
        }

        $budget = new Budget(
            $user,
            $data->year,
            $data->month,
            Money::fromPrimitives($data->plannedIncome->amount, $data->plannedIncome->currency)
        );

        $this->processLimits($data, $user, $budget);

        $this->entityManager->persist($budget);
        $this->entityManager->flush();

        return $budget;
    }

    private function updateBudget(Budget $budget, BudgetInput $data, User $user): Budget
    {
        //Get entity to register it in UOW
        $budgetEntity = $this->budgetRepository->find($budget->getId());
        $budgetEntity->setPlannedIncome(
            Money::fromPrimitives($data->plannedIncome->amount, $data->plannedIncome->currency)
        );

        $budgetEntity->getBudgetLimits()->clear();

        $this->processLimits($data, $user, $budgetEntity);
        $this->entityManager->flush();

        return $budgetEntity;
    }

    private function processLimits(BudgetInput $data, User $user, Budget $budget): void
    {
        if (empty($data->limits)) {
            return;
        }

        $subcategoryIds = array_map(fn ($limit) => $limit->subcategoryId, $data->limits);
        $subcategories = $this->subcategoryRepository->findBy(['id' => $subcategoryIds, 'user' => $user]);

        if (count($subcategories) !== count($subcategoryIds)) {
            throw new BadRequestHttpException('One or more subcategories are invalid or do not belong to the user.');
        }

        /** @var array<string, Subcategory> $indexedSubcategories */
        $indexedSubcategories = [];
        foreach ($subcategories as $subcategory) {
            $indexedSubcategories[$subcategory->getId()->toRfc4122()] = $subcategory;
        }

        foreach ($data->limits as $limitDto) {
            $subcategory = $indexedSubcategories[$limitDto->subcategoryId] ?? null;
            if ($subcategory === null) {
                // This case should theoretically not be reached due to the count check above, but it's a safeguard.
                throw new BadRequestHttpException("Subcategory with ID {$limitDto->subcategoryId} not found.");
            }

            $limit = new BudgetLimit(
                $budget,
                $subcategory,
                Money::fromPrimitives($limitDto->limitAmount->amount, $limitDto->limitAmount->currency)
            );
            $budget->getBudgetLimits()->add($limit);
        }
    }
}
