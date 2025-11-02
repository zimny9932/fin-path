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

    public function testCreateSubcategorySuccess(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/subcategories', [
            'json' => [
                'name' => 'Internet Bill',
                'type' => 'expense',
                'mainCategory' => 'Housing',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains([
            'name' => 'Internet Bill',
            'type' => 'expense',
            'mainCategory' => 'Housing',
        ]);

        $subcategoryRepository = self::getContainer()->get(\App\Repository\SubcategoryRepository::class);
        $savedSubcategory = $subcategoryRepository->findOneByNameAndUser('Internet Bill', $user);
        self::assertNotNull($savedSubcategory);
        self::assertSame('Internet Bill', $savedSubcategory->getName());
    }

    public function testCreateSubcategoryValidationError(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/subcategories', [
            'json' => [
                'name' => 'a', // too short
                'type' => 'invalid_type',
                'mainCategory' => 'INVALID_CATEGORY',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => 'Subcategory name must be at least 2 characters long'],
                ['propertyPath' => 'type', 'message' => 'The value you selected is not a valid choice.'],
                ['propertyPath' => 'mainCategory', 'message' => 'The value you selected is not a valid choice.'],
            ],
        ]);
    }

    public function testCreateSubcategoryConflict(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createSubcategory($user, 'Salary', TransactionType::INCOME, MainCategory::SALARY);
        $client = $this->createClientWithCredentials($user);

        $client->request('POST', '/api/subcategories', [
            'json' => [
                'name' => 'Salary',
                'type' => 'income',
                'mainCategory' => 'Salary',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains([
            'message' => 'Subcategory with the same name already exists for this user.',
        ]);
    }

    public function testCreateSubcategoryFailsForUnauthenticatedUser(): void
    {
        static::createClient()->request('POST', '/api/subcategories', [
            'json' => [
                'name' => 'Internet Bill',
                'type' => 'expense',
                'mainCategory' => 'Housing',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateSubcategory(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = new Subcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->entityManager()->persist($subcategory);
        $this->entityManager()->flush();

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/subcategories/' . $subcategory->getId(), [
            'json' => [
                'name' => 'Groceries & Food',
                'type' => 'expense',
                'mainCategory' => 'Food',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'name' => 'Groceries & Food',
            'mainCategory' => 'Food',
        ]);
    }

    public function testUpdateSubcategoryWithExistingName(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $this->createSubcategory($user, 'Bills', TransactionType::EXPENSE, MainCategory::HOUSING);

        $subcategoryToUpdate = new Subcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->entityManager()->persist($subcategoryToUpdate);
        $this->entityManager()->flush();

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/subcategories/' . $subcategoryToUpdate->getId(), [
            'json' => [
                'name' => 'Bills', // Existing name
                'type' => 'expense',
                'mainCategory' => 'Housing',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains([
            'message' => 'Subcategory with the same name already exists for this user.',
        ]);
    }

    public function testUpdateSubcategoryOfAnotherUser(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $anotherUser = $this->createUser('another-user@example.com', 'password');
        $subcategoryOfAnotherUser = new Subcategory($anotherUser, 'Another User Subcategory', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->entityManager()->persist($subcategoryOfAnotherUser);
        $this->entityManager()->flush();

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/subcategories/' . $subcategoryOfAnotherUser->getId(), [
            'json' => [
                'name' => 'Attempt to update',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateSubcategoryWithInvalidData(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = new Subcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->entityManager()->persist($subcategory);
        $this->entityManager()->flush();

        $client = $this->createClientWithCredentials($user);
        $client->request('PUT', '/api/subcategories/' . $subcategory->getId(), [
            'json' => [
                'name' => 'a', // Too short
                'type' => 'invalid_type',
                'mainCategory' => 'INVALID_CATEGORY',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['propertyPath' => 'name', 'message' => 'Subcategory name must be at least 2 characters long'],
                ['propertyPath' => 'type', 'message' => 'The value you selected is not a valid choice.'],
                ['propertyPath' => 'mainCategory', 'message' => 'The value you selected is not a valid choice.'],
            ],
        ]);
    }

    public function testUpdateNonExistentSubcategory(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $nonExistentUuid = \Symfony\Component\Uid\Uuid::v7();

        $client->request('PUT', '/api/subcategories/' . $nonExistentUuid, [
            'json' => [
                'name' => 'This will fail',
                'type' => 'expense',
                'mainCategory' => 'Food',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteSubcategorySuccess(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'To be deleted', TransactionType::EXPENSE, MainCategory::OTHER);

        $client = $this->createClientWithCredentials($user);
        $client->request('DELETE', '/api/subcategories/' . $subcategory->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->entityManager()->clear();
        $deletedSubcategory = $this->entityManager()->find(Subcategory::class, $subcategory->getId());
        self::assertNull($deletedSubcategory);
    }

    public function testDeleteSubcategoryInUse(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->createTransaction($user, $subcategory, 10000, new \DateTimeImmutable());

        $client = $this->createClientWithCredentials($user);
        $client->request('DELETE', '/api/subcategories/' . $subcategory->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains([
            'detail' => 'This subcategory cannot be deleted because it is associated with existing transactions.',
        ]);
    }

    public function testDeleteSubcategoryOfAnotherUser(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $anotherUser = $this->createUser('another-user@example.com', 'password');
        $subcategoryOfAnotherUser = $this->createSubcategory($anotherUser, 'Another User Subcategory', TransactionType::EXPENSE, MainCategory::FOOD);

        $client = $this->createClientWithCredentials($user);
        $client->request('DELETE', '/api/subcategories/' . $subcategoryOfAnotherUser->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteNonExistentSubcategory(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);
        $nonExistentUuid = \Symfony\Component\Uid\Uuid::v7();

        $client->request('DELETE', '/api/subcategories/' . $nonExistentUuid);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteSubcategoryUnauthenticated(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $subcategory = $this->createSubcategory($user, 'Some Subcategory', TransactionType::EXPENSE, MainCategory::FOOD);

        static::createClient()->request('DELETE', '/api/subcategories/' . $subcategory->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function createSubcategory(User $user, string $name, TransactionType $type, MainCategory $mainCategory): Subcategory
    {
        $subcategory = new Subcategory($user, $name, $type, $mainCategory);
        $this->entityManager()->persist($subcategory);
        $this->entityManager()->flush();

        return $subcategory;
    }
}
