<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TransactionGetApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testGetTransactionsUnauthenticated(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/transactions');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetTransactionsSuccess(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other-user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $otherSubcategory = $this->createSubcategory($otherUser, 'Salary', TransactionType::INCOME, MainCategory::SALARY);

        $this->createTransaction($user, $subcategory, 10000, new \DateTimeImmutable('2025-01-05'));
        $this->createTransaction($user, $subcategory, 5000, new \DateTimeImmutable('2025-01-10'));
        $this->createTransaction($otherUser, $otherSubcategory, 25000, new \DateTimeImmutable('2025-01-15'));

        $client = $this->createClientWithCredentials($user);
        $response = $client->request('GET', '/api/transactions');

        self::assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        self::assertSame(2, $data['totalItems']);
        self::assertCount(2, $data['member']);
        self::assertSame(10000, $data['member'][0]['amount']['amount']);
    }

    public function testGetTransactionsWithDateFilters(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createTransaction($user, $subcategory, 10000, new \DateTimeImmutable('2025-01-05'));
        $this->createTransaction($user, $subcategory, 5000, new \DateTimeImmutable('2025-01-10'));
        $this->createTransaction($user, $subcategory, 7500, new \DateTimeImmutable('2025-01-15'));

        $client = $this->createClientWithCredentials($user);
        $response = $client->request('GET', '/api/transactions?date[after]=2025-01-06&date[before]=2025-01-14');

        self::assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);

        self::assertSame(1, $data['totalItems']);
        self::assertCount(1, $data['member']);
        self::assertSame(5000, $data['member'][0]['amount']['amount']);
    }

    public function testGetTransactionsWithSorting(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createTransaction($user, $subcategory, 10000, new \DateTimeImmutable('2025-01-05'));
        $this->createTransaction($user, $subcategory, 5000, new \DateTimeImmutable('2025-01-10'));

        $client = $this->createClientWithCredentials($user);

        // Sort by date ascending
        $response = $client->request('GET', '/api/transactions?sortOrder[date]=asc');
        self::assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        self::assertSame('2025-01-05', $data['member'][0]['date']);

        // Sort by amount descending
        $response = $client->request('GET', '/api/transactions?sortOrder[amount.amount]=desc');
        self::assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        self::assertSame(10000, $data['member'][0]['amount']['amount']);
    }

    public function testGetTransactionsWithPagination(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        for ($i = 0; $i < 5; ++$i) {
            $this->createTransaction($user, $subcategory, 1000 * ($i + 1), new \DateTimeImmutable("2025-01-0".($i + 1)));
        }

        $client = $this->createClientWithCredentials($user);
        $response = $client->request('GET', '/api/transactions?itemsPerPage=2&page=2');

        self::assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        self::assertSame(5, $data['totalItems']);
        self::assertCount(2, $data['member']);
        self::assertSame(3000, $data['member'][0]['amount']['amount']);
    }
}
