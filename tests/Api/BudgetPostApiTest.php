<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Repository\BudgetRepository;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BudgetPostApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testCreateBudgetWithLimitsSuccess(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);

        $response = $client->request('POST', '/api/budgets', [
            'json' => [
                'year' => 2025,
                'month' => 11,
                'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
                'limits' => [
                    [
                        'subcategoryId' => $subcategory->getId()->toRfc4122(),
                        'limitAmount' => ['amount' => 40000, 'currency' => 'PLN'],
                    ],
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            'year' => 2025,
            'month' => 11,
            'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
            'budgetLimits' => [
                [
                    'subcategory' => [
                        'id' => $subcategory->getId()->toRfc4122(),
                        'name' => 'Groceries',
                    ],
                    'limitAmount' => ['amount' => 40000, 'currency' => 'PLN'],
                ],
            ],
        ]);

        /** @var BudgetRepository $budgetRepository */
        $budgetRepository = self::getContainer()->get(BudgetRepository::class);
        $budget = $budgetRepository->findOneBy(['user' => $user, 'year' => 2025, 'month' => 11]);
        self::assertNotNull($budget);
        self::assertCount(1, $budget->getBudgetLimits());
    }

    public function testCreateBudgetWithoutLimitsSuccess(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/budgets', [
            'json' => [
                'year' => 2026,
                'month' => 1,
                'plannedIncome' => ['amount' => 700000, 'currency' => 'PLN'],
                'limits' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJsonContains([
            'year' => 2026,
            'month' => 1,
            'budgetLimits' => [],
        ]);

        /** @var BudgetRepository $budgetRepository */
        $budgetRepository = self::getContainer()->get(BudgetRepository::class);
        $budget = $budgetRepository->findOneBy(['user' => $user, 'year' => 2026, 'month' => 1]);
        self::assertNotNull($budget);
        self::assertCount(0, $budget->getBudgetLimits());
    }

    public function testCreateBudgetForExistingPeriodFails(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createBudget($user, 2025, 11, Money::fromPrimitives(100000, 'PLN'));
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/budgets', [
            'json' => [
                'year' => 2025,
                'month' => 11,
                'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
                'budgetLimits' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains([
            'message' => 'Budget for this period already exists.',
        ]);
    }

    public function testCreateBudgetWithInvalidDataFails(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/budgets', [
            'json' => [
                'year' => 2000, // Invalid year
                'month' => 13,  // Invalid month
                'plannedIncome' => ['amount' => -100, 'currency' => 'INVALID'],
                'limits' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['propertyPath' => 'year'],
                ['propertyPath' => 'month'],
                ['propertyPath' => 'plannedIncome.amount'],
                ['propertyPath' => 'plannedIncome.currency'],
            ],
        ]);
    }

    public function testCreateBudgetUnauthenticatedFails(): void
    {
        self::createClient()->request('POST', '/api/budgets', [
            'json' => [
                'year' => 2025,
                'month' => 11,
                'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
                'limits' => [],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateBudgetWithAnotherUserSubcategoryFails(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $otherUserSubcategory = $this->createSubcategory($otherUser, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);

        $client->request('POST', '/api/budgets', [
            'json' => [
                'year' => 2025,
                'month' => 11,
                'plannedIncome' => ['amount' => 650000, 'currency' => 'PLN'],
                'limits' => [
                    [
                        'subcategoryId' => $otherUserSubcategory->getId()->toRfc4122(),
                        'limitAmount' => ['amount' => 40000, 'currency' => 'PLN'],
                    ],
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertJsonContains([
            'detail' => 'One or more subcategories are invalid or do not belong to the user.',
        ]);
    }
}
