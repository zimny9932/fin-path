<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Subcategory;
use App\Entity\Transaction;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Symfony\Component\Uid\Uuid;

class TransactionGetApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testGetOwnTransactionReturns200Ok(): void
    {
        $user = $this->createUser('test@user.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $transaction = new Transaction(
            $user,
            $subcategory,
            Money::fromPrimitives(12345, 'PLN'),
            new \DateTimeImmutable('2025-01-01'),
            'Test description'
        );
        $this->entityManager()->persist($transaction);
        $this->entityManager()->flush();

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/transactions/' . $transaction->getId());

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $transaction->getId()->__toString(),
            'amount' => ['amount' => 12345, 'currency' => 'PLN'],
            'date' => '2025-01-01',
            'description' => 'Test description',
            'subcategory' => [
                'id' => $subcategory->getId()->__toString(),
                'name' => 'Groceries',
                'mainCategory' => 'Food',
                'type' => 'expense',
            ],
        ]);
    }

    public function testGetAnotherUserTransactionReturns404NotFound(): void
    {
        $owner = $this->createUser('owner@user.com', 'password');
        $subcategory = $this->createSubcategory($owner, 'Groceries');
        $transaction = new Transaction(
            $owner,
            $subcategory,
            Money::fromPrimitives(100, 'PLN'),
            new \DateTimeImmutable(),
            null
        );
        $this->entityManager()->persist($transaction);
        $this->entityManager()->flush();

        $attacker = $this->createUser('attacker@user.com', 'password');

        $client = $this->createClientWithCredentials($attacker);
        $client->request('GET', '/api/transactions/' . $transaction->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentTransactionReturns404NotFound(): void
    {
        $user = $this->createUser('test@user.com', 'password');

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/transactions/' . Uuid::v7()->__toString());

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetTransactionWithoutAuthenticationReturns401Unauthorized(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/transactions/' . Uuid::v7()->__toString());

        self::assertResponseStatusCodeSame(401);
    }
}
