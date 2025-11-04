<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Repository\BudgetRepository;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BudgetPutApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testUpdateBudgetSuccess(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $budget = $this->createBudget($user, 2025, 11, Money::fromPrimitives(600000, 'PLN'));
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createBudgetLimit($budget, $subcategory, Money::fromPrimitives(50000, 'PLN'));
        $newSubcategory = $this->createSubcategory($user, 'Rent', TransactionType::EXPENSE, MainCategory::OTHER);

        $response = $client->request('PUT', '/api/budgets/2025/11', [
            'json' => [
                'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
                'limits' => [
                    [
                        'subcategoryId' => $newSubcategory->getId()->toRfc4122(),
                        'limitAmount' => ['amount' => 250000, 'currency' => 'PLN'],
                    ],
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
            'budgetLimits' => [
                [
                    'subcategory' => ['name' => 'Rent'],
                    'limitAmount' => ['amount' => 250000, 'currency' => 'PLN'],
                ],
            ],
        ]);

        /** @var BudgetRepository $budgetRepository */
        $budgetRepository = self::getContainer()->get(BudgetRepository::class);
        $updatedBudget = $budgetRepository->find($budget->getId());
        self::assertNotNull($updatedBudget);
        self::assertCount(1, $updatedBudget->getBudgetLimits());
        self::assertEquals('Rent', $updatedBudget->getBudgetLimits()->first()->getSubcategory()->getName());
    }

    public function testUpdateNonExistentBudgetFails(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('PUT', '/api/budgets/2025/11', [
            'json' => ['plannedIncome' => ['amount' => 1, 'currency' => 'PLN'], 'limits' => []]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateBudgetOfAnotherUserFails(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other@example.com', 'password');
        $this->createBudget($otherUser, 2025, 11, Money::fromPrimitives(100, 'PLN'));
        $client = $this->createClientWithCredentials($user);

        $client->request('PUT', '/api/budgets/2025/11', [
            'json' => ['plannedIncome' => ['amount' => 1, 'currency' => 'PLN'], 'limits' => []]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateBudgetUnauthenticatedFails(): void
    {
        self::createClient()->request('PUT', '/api/budgets/2025/11', [
            'json' => ['plannedIncome' => ['amount' => 1, 'currency' => 'PLN'], 'limits' => []]
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateBudgetWithInvalidDataFails(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createBudget($user, 2025, 11, Money::fromPrimitives(100, 'PLN'));
        $client = $this->createClientWithCredentials($user);

        $client->request('PUT', '/api/budgets/2025/11', [
            'json' => [
                'plannedIncome' => ['amount' => -100, 'currency' => 'INVALID'],
                'limits' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['propertyPath' => 'plannedIncome.amount'],
                ['propertyPath' => 'plannedIncome.currency'],
            ],
        ]);
    }
}
