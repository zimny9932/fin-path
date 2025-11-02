<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BudgetGetApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testGetBudget(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries');
        $budget = $this->createBudget($user, 2025, 10, Money::fromPrimitives(500000, 'PLN'));
        $this->createBudgetLimit($budget, $subcategory, Money::fromPrimitives(50000, 'PLN'));

        $client = $this->createClientWithCredentials($user);

        $client->request('GET', '/api/budgets/2025/10');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            'id' => $budget->getId()->toString(),
            'year' => 2025,
            'month' => 10,
            'plannedIncome' => [
                'amount' => 500000,
                'currency' => 'PLN',
            ],
            'budgetLimits' => [
                [
                    'subcategory' => [
                        'name' => 'Groceries',
                    ],
                ],
            ],
        ]);
    }

    public function testGetNonExistentBudget(): void
    {
        $user = $this->createUser('user@example.com', 'password');

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/budgets/2025/10');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetBudgetOfAnotherUser(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other-user@example.com', 'password');
        $this->createBudget($otherUser, 2025, 10, Money::fromPrimitives(500000, 'PLN'));

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/budgets/2025/10');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetBudgetUnauthenticated(): void
    {
        self::createClient()->request('GET', '/api/budgets/2025/10');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetBudgetWithInvalidMonth(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/budgets/2025/13');
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
