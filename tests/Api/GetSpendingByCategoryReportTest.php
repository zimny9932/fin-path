<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Enum\MainCategory;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetSpendingByCategoryReportTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testGetSpendingByCategoryReportAsAnonymous(): void
    {
        static::createClient()->request('GET', '/api/reports/spending-by-category');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetSpendingByCategoryReportWithNoTransactions(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('GET', '/api/reports/spending-by-category');
        self::assertResponseIsSuccessful();
        self::assertJsonContains([]);
    }

    public function testGetSpendingByCategoryReportWithInvalidDateFormat(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('GET', '/api/reports/spending-by-category?startDate=invalid-date');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetSpendingByCategoryReportWithInvalidDateRange(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('GET', '/api/reports/spending-by-category?startDate=2025-11-10&endDate=2025-11-01');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetSpendingByCategoryReportWithData(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $user->setBillingCycleStartDay(1);

        $food = $this->createSubcategory($user, 'Groceries', mainCategory: MainCategory::FOOD);
        $transport = $this->createSubcategory($user, 'Fuel', mainCategory: MainCategory::TRANSPORT);
        $housing = $this->createSubcategory($user, 'Rent', mainCategory: MainCategory::HOUSING);

        $this->createTransaction($user, $food, Money::fromPrimitives(10000, 'PLN'), new \DateTimeImmutable('2025-11-02'));
        $this->createTransaction($user, $food, Money::fromPrimitives(5000, 'PLN'), new \DateTimeImmutable('2025-11-03'));
        $this->createTransaction($user, $transport, Money::fromPrimitives(25000, 'PLN'), new \DateTimeImmutable('2025-11-05'));
        $this->createTransaction($user, $housing, Money::fromPrimitives(10000, 'PLN'), new \DateTimeImmutable('2025-11-10'));
        // Transaction from another month
        $this->createTransaction($user, $food, Money::fromPrimitives(9999, 'PLN'), new \DateTimeImmutable('2025-10-10'));

        $response = $client->request('GET', '/api/reports/spending-by-category?startDate=2025-11-01&endDate=2025-11-30');
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'member' => [
                [
                    'mainCategory' => 'Transport',
                    'totalAmount' => ['amount' => 25000, 'currency' => 'PLN'],
                    'percentageOfTotal' => 50,
                ],
                [
                    'mainCategory' => 'Food',
                    'totalAmount' => ['amount' => 15000, 'currency' => 'PLN'],
                    'percentageOfTotal' => 30,
                ],
                [
                    'mainCategory' => 'Housing',
                    'totalAmount' => ['amount' => 10000, 'currency' => 'PLN'],
                    'percentageOfTotal' => 20,
                ],
            ]
        ]);
        self::assertCount(3, $response->toArray()['member']);
    }
}
