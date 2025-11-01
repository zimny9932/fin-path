<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SubcategoryApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testGetSubcategoriesCollection(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other-user@example.com', 'password');

        $this->createSubcategory($user, 'Salary', TransactionType::INCOME, MainCategory::SALARY);
        $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createSubcategory($otherUser, 'Other User Salary', TransactionType::INCOME, MainCategory::SALARY);

        $client = $this->createClientWithCredentials($user);
        $response = $client->request('GET', '/api/subcategories');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            'totalItems' => 2,
        ]);

        self::assertCount(2, $response->toArray()['member']);
        self::assertJsonContains([
            'member' => [
                ['name' => 'Salary'],
            ],
        ]);
    }

    public function testGetSubcategoriesFilteredByType(): void
    {
        $user = $this->createUser('user@example.com', 'password');

        $this->createSubcategory($user, 'Salary', TransactionType::INCOME, MainCategory::SALARY);
        $this->createSubcategory($user, 'Side hustle', TransactionType::INCOME, MainCategory::BUSINESS);
        $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);

        $client = $this->createClientWithCredentials($user);

        // Test filtering by income
        $responseIncome = $client->request('GET', '/api/subcategories?type=income');
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['totalItems' => 2]);
        self::assertCount(2, $responseIncome->toArray()['member']);
        self::assertJsonContains([
            'member' => [
                ['name' => 'Salary'],
                ['name' => 'Side hustle'],
            ],
        ]);

        // Test filtering by expense
        $responseExpense = $client->request('GET', '/api/subcategories?type=expense');
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['totalItems' => 1]);
        self::assertCount(1, $responseExpense->toArray()['member']);
        self::assertJsonContains([
            'member' => [
                ['name' => 'Groceries'],
            ],
        ]);
    }

    public function testGetSubcategoriesFailsForUnauthenticatedUser(): void
    {
        static::createClient()->request('GET', '/api/subcategories');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function createSubcategory(User $user, string $name, TransactionType $type, MainCategory $mainCategory): void
    {
        $subcategory = new Subcategory($user, $name, $type, $mainCategory);
        $this->entityManager()->persist($subcategory);
        $this->entityManager()->flush();
    }
}
