<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TransactionPostApiTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::$alwaysBootKernel = false;
    }

    public function testPostTransactionRequiresAuthentication(): void
    {
        static::createClient()->request('POST', '/api/transactions', [
            'json' => [],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testPostTransactionFailsWithInvalidData(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/transactions', [
            'json' => [
                'subcategoryId' => 'not-a-uuid',
                'amount' => [
                    'amount' => -100,
                    'currency' => 'INVALID',
                ],
                'date' => 'not-a-date',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['propertyPath' => 'subcategoryId', 'message' => 'This is not a valid UUID.'],
                ['propertyPath' => 'amount.amount', 'message' => 'This value should be positive.'],
                ['propertyPath' => 'amount.currency', 'message' => 'This value is not a valid currency.'],
                ['propertyPath' => 'date', 'message' => 'This value is not a valid date.'],
            ],
        ]);
    }

    public function testPostTransactionFailsForNonExistentSubcategory(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/transactions', [
            'json' => [
                'subcategoryId' => '018f430c-b52a-7b3c-9226-95c96328a86c',
                'amount' => ['amount' => 10000, 'currency' => 'PLN'],
                'date' => '2025-11-01',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostTransactionFailsForSubcategoryOfAnotherUser(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $otherUser = $this->createUser('other@example.com', 'password');
        $otherUserSubcategory = $this->createSubcategory($otherUser, 'Other User Salary');

        $client = $this->createClientWithCredentials($user);
        $client->request('POST', '/api/transactions', [
            'json' => [
                'subcategoryId' => (string) $otherUserSubcategory->getId(),
                'amount' => ['amount' => 10000, 'currency' => 'PLN'],
                'date' => '2025-11-01',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostTransactionSucceeds(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries');
        $client = $this->createClientWithCredentials($user);

        $response = $client->request('POST', '/api/transactions', [
            'json' => [
                'subcategoryId' => (string) $subcategory->getId(),
                'amount' => ['amount' => 12345, 'currency' => 'PLN'],
                'date' => '2025-11-02',
                'description' => 'Weekly shopping',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJsonContains([
            'amount' => ['amount' => 12345, 'currency' => 'PLN'],
            'date' => '2025-11-02',
            'description' => 'Weekly shopping',
            'subcategory' => [
                'id' => (string) $subcategory->getId(),
                'name' => 'Groceries',
            ],
        ]);

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
    }
}
