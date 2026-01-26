<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use App\Tests\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TransactionPutApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testUpdateTransactionRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/transactions/018f3a2b-8b49-7c98-a532-3492576b291c', [
            'json' => [],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateTransactionFailsForAnotherUsersTransaction(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other@example.com', 'password');

        $subcategory = $this->createSubcategory($otherUser, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $transaction = $this->createTransaction(
            $otherUser,
            $subcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-11-01')
        );

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/transactions/' . $transaction->getId(), [
            'json' => [
                'subcategoryId' => (string)$subcategory->getId(),
                'amount' => ['amount' => 15000, 'currency' => 'PLN'],
                'date' => '2025-11-02',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTransactionFailsForNonExistentSubcategory(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries');
        $transaction = $this->createTransaction(
            $user,
            $subcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-11-01')
        );

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/transactions/' . $transaction->getId(), [
            'json' => [
                'subcategoryId' => '018f430c-b52a-7b3c-9226-95c96328a86c', // Non-existent UUID
                'amount' => ['amount' => 15000, 'currency' => 'PLN'],
                'date' => '2025-11-02',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTransactionFailsForSubcategoryOfAnotherUser(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other@example.com', 'password');
        $userSubcategory = $this->createSubcategory($user, 'User Subcategory');
        $otherUserSubcategory = $this->createSubcategory($otherUser, 'Other User Subcategory');
        $transaction = $this->createTransaction(
            $user,
            $userSubcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-11-01')
        );

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/transactions/' . $transaction->getId(), [
            'json' => [
                'subcategoryId' => (string)$otherUserSubcategory->getId(),
                'amount' => ['amount' => 15000, 'currency' => 'PLN'],
                'date' => '2025-11-02',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTransactionFailsWithInvalidData(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries');
        $transaction = $this->createTransaction(
            $user,
            $subcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-11-01')
        );

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/transactions/' . $transaction->getId(), [
            'json' => [
                'subcategoryId' => 'not-a-uuid',
                'amount' => ['amount' => -100, 'currency' => 'INVALID'],
                'date' => 'not-a-date',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['propertyPath' => 'subcategoryId', 'message' => 'This is not a valid UUID.'],
                ['propertyPath' => 'amount.amount', 'message' => 'This value should be either positive or zero.'],
                ['propertyPath' => 'amount.currency', 'message' => 'This value is not a valid currency.'],
                ['propertyPath' => 'date', 'message' => 'This value is not a valid date.'],
            ],
        ]);
    }

    public function testUpdateTransactionSucceeds(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $originalSubcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $newSubcategory = $this->createSubcategory($user, 'Restaurant', TransactionType::EXPENSE, MainCategory::FOOD);
        $transaction = $this->createTransaction(
            $user,
            $originalSubcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-11-01'),
            'Initial description'
        );

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/transactions/' . $transaction->getId(), [
            'json' => [
                'subcategoryId' => (string)$newSubcategory->getId(),
                'amount' => ['amount' => 15000, 'currency' => 'PLN'],
                'date' => '2025-11-02',
                'description' => 'Updated description',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'id' => (string)$transaction->getId(),
            'amount' => ['amount' => 15000, 'currency' => 'PLN'],
            'date' => '2025-11-02',
            'description' => 'Updated description',
            'subcategory' => [
                'id' => (string)$newSubcategory->getId(),
                'name' => 'Restaurant',
            ],
        ]);
    }
}
