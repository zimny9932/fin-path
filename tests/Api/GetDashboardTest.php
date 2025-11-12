<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnection;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;

final class GetDashboardTest extends ApiTestCase
{
    private const URI = '/api/dashboard';

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testShouldReturnUnauthorizedWhenUserIsNotLoggedIn(): void
    {
        static::createClient()->request('GET', self::URI);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testShouldReturnDashboardDataForUserWithoutOnboarding(): void
    {
        $user = $this->createUser('test@test.pl', 'password');
        $client = $this->createClientWithCredentials($user);

        $response = $client->request('GET', self::URI);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = json_decode($response->getContent(), true);
        $this->assertStringContainsString('-01T', $responseContent['billingCycle']['startDate']);
    }

    public function testShouldReturnDashboardDataWithBudget(): void
    {
        $user = $this->createUser('test@test.pl', 'password');
        $user->setBillingCycleStartDay(25);
        $this->entityManager()->flush();
        $client = $this->createClientWithCredentials($user);

        $subcategory = $this->createSubcategory(
            $user,
            'Groceries',
            TransactionType::EXPENSE,
            MainCategory::FOOD
        );
        $this->createTransaction(
            $user,
            $subcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new DateTimeImmutable(),
            'Test transaction'
        );

        $budget = $this->createBudget($user, (int) (new DateTimeImmutable())->format('Y'), (int) (new DateTimeImmutable())->format('m'), Money::fromPrimitives(500000, 'PLN'));
        $this->createBudgetLimit($budget, $subcategory, Money::fromPrimitives(50000, 'PLN'));

        $client->request('GET', self::URI);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'summary' => [
                'totalExpenses' => ['amount' => 10000, 'currency' => 'PLN'],
            ],
            'budgetProgress' => [
                'planned' => ['amount' => 50000, 'currency' => 'PLN'],
                'spent' => ['amount' => 10000, 'currency' => 'PLN'],
                'percentage' => 20,
            ],
        ]);
    }
}
