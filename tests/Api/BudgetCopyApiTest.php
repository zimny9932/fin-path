<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Budget;
use App\Entity\BudgetLimit;
use App\Entity\Subcategory;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class BudgetCopyApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testCopyBudgetHappyPath(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $sourceYear = 2025;
        $sourceMonth = 10;
        $targetYear = 2025;
        $targetMonth = 11;

        $subcategory1 = $this->createSubcategory($user, 'Groceries');
        $subcategory2 = $this->createSubcategory($user, 'Transport');

        $sourceBudget = $this->createBudget($user, $sourceYear, $sourceMonth, Money::fromPrimitives(500000, 'PLN'));
        $this->createBudgetLimit($sourceBudget, $subcategory1, Money::fromPrimitives(100000, 'PLN'));
        $this->createBudgetLimit($sourceBudget, $subcategory2, Money::fromPrimitives(50000, 'PLN'));

        $response = $client->request('POST', "/api/budgets/{$targetYear}/{$targetMonth}/copy", [
            'json' => [
                'sourceYear' => $sourceYear,
                'sourceMonth' => $sourceMonth,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $response->toArray();

        self::assertSame($targetYear, $data['year']);
        self::assertSame($targetMonth, $data['month']);
        self::assertSame(500000, $data['plannedIncome']['amount']);
        self::assertCount(2, $data['budgetLimits']);

        // Check if limits are copied correctly
        $limitSubcategoryIds = array_map(fn($limit) => $limit['subcategory']['id'], $data['budgetLimits']);
        self::assertContains($subcategory1->getId()->__toString(), $limitSubcategoryIds);
        self::assertContains($subcategory2->getId()->__toString(), $limitSubcategoryIds);
    }

    public function testCopyBudgetSourceNotFound(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $response = $client->request('POST', '/api/budgets/2025/11/copy', [
            'json' => [
                'sourceYear' => 2025,
                'sourceMonth' => 10,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCopyBudgetTargetAlreadyExists(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $sourceYear = 2025;
        $sourceMonth = 10;
        $targetYear = 2025;
        $targetMonth = 11;

        $this->createBudget($user, $sourceYear, $sourceMonth, Money::fromPrimitives(500000, 'PLN'));
        $this->createBudget($user, $targetYear, $targetMonth, Money::fromPrimitives(600000, 'PLN'));

        $client->request('POST', "/api/budgets/{$targetYear}/{$targetMonth}/copy", [
            'json' => [
                'sourceYear' => $sourceYear,
                'sourceMonth' => $sourceMonth,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testCopyBudgetUnauthorized(): void
    {
        $this->createUser('user@example.com', 'password');

        static::createClient()->request('POST', '/api/budgets/2025/11/copy', [
            'json' => [
                'sourceYear' => 2025,
                'sourceMonth' => 10,
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCopyBudgetInvalidInput(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/budgets/2025/11/copy', [
            'json' => [
                'sourceYear' => 2025,
                'sourceMonth' => 99, // Invalid month
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
