<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Subcategory;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RecentSubcategoriesApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testGetRecentSubcategoriesFailsForUnauthenticatedUser(): void
    {
        static::createClient()->request('GET', '/api/recent-subcategories');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetRecentSubcategoriesReturnsEmptyForUserWithoutTransactions(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/recent-subcategories');

        self::assertResponseIsSuccessful();
        self::assertJsonContains(['totalItems' => 0]);
    }

    public function testGetRecentSubcategoriesReturnsCorrectlyOrderedSubcategoriesWithDefaultLimit(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createSubcategory($user, 'Salary', TransactionType::INCOME, MainCategory::SALARY);
        $this->createSubcategory($user, 'Fuel', TransactionType::EXPENSE, MainCategory::TRANSPORT);
        $this->createSubcategory($user, 'Internet', TransactionType::EXPENSE, MainCategory::HOUSING);
        $this->createSubcategory($user, 'Gifts', TransactionType::EXPENSE, MainCategory::GIFTS);
        $this->createSubcategory($user, 'Side Hustle', TransactionType::INCOME, MainCategory::BUSINESS);

        // Dates are important for ordering
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Salary'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-01')
        ); // 6th most recent
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Gifts'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-02')
        ); // 5th
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Internet'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-03')
        ); // 4th
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Fuel'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-04')
        ); // 3rd
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Groceries'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-05')
        ); // 2nd
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Side Hustle'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-06')
        ); // 1st

        $client = $this->createClientWithCredentials($user);
        $response = $client->request('GET', '/api/recent-subcategories');

        self::assertResponseIsSuccessful();
        $responseArray = $response->toArray();
        self::assertCount(5, $responseArray['member']);
        self::assertSame('Side Hustle', $responseArray['member'][0]['name']);
        self::assertSame('Groceries', $responseArray['member'][1]['name']);
        self::assertSame('Fuel', $responseArray['member'][2]['name']);
        self::assertSame('Internet', $responseArray['member'][3]['name']);
        self::assertSame('Gifts', $responseArray['member'][4]['name']);
    }

    public function testGetRecentSubcategoriesRespectsCustomLimit(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createSubcategory($user, 'Salary', TransactionType::INCOME, MainCategory::SALARY);
        $this->createSubcategory($user, 'Fuel', TransactionType::EXPENSE, MainCategory::TRANSPORT);

        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Salary'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-01')
        );
        $this->createTransaction($user, $this->getSubcategoryByName('Fuel'), Money::fromPrimitives(10000, 'PLN'), new \DateTimeImmutable('2025-01-02'));
        $this->createTransaction(
            $user,
            $this->getSubcategoryByName('Groceries'),
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-01-03')
        );

        $client = $this->createClientWithCredentials($user);
        $response = $client->request('GET', '/api/recent-subcategories?limit=2');

        self::assertResponseIsSuccessful();
        $responseArray = $response->toArray();
        self::assertCount(2, $responseArray['member']);
        self::assertSame('Groceries', $responseArray['member'][0]['name']);
        self::assertSame('Fuel', $responseArray['member'][1]['name']);
    }

    public function testGetRecentSubcategoriesReturnsBadRequestForInvalidLimit(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('GET', '/api/recent-subcategories?limit=0');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $client->request('GET', '/api/recent-subcategories?limit=-1');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $client->request('GET', '/api/recent-subcategories?limit=abc');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    private function getSubcategoryByName(string $name): Subcategory
    {
        return $this->entityManager()->getRepository(Subcategory::class)->findOneBy(['name' => $name]);
    }
}
