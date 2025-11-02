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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class TransactionDeleteApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
    }

    public function testDeleteTransactionSucceedsForOwner(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $transaction = $this->createTransaction(
            $user,
            $subcategory,
            Money::fromPrimitives(10000, 'PLN'),
            new \DateTimeImmutable('2025-11-01')
        );

        $client = $this->createClientWithCredentials($user);
        $client->request('DELETE', '/api/transactions/' . $transaction->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->clear();
        $deletedTransaction = $em->find(Transaction::class, $transaction->getId());
        self::assertNull($deletedTransaction);
    }

    public function testDeleteTransactionRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/transactions/018f3a2b-8b49-7c98-a532-3492576b291c');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteTransactionFailsForAnotherUsersTransaction(): void
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
        $client->request('DELETE', '/api/transactions/' . $transaction->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentTransaction(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $client->request('DELETE', '/api/transactions/018f3a2b-8b49-7c98-a532-3492576b291c');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteTransactionWithInvalidUuid(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $client->request('DELETE', '/api/transactions/not-a-uuid');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
