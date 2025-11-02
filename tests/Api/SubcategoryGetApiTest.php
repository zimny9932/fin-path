<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Repository\SubcategoryRepository;
use App\Repository\UserRepository;
use App\Tests\ApiTestCase;
use Symfony\Component\Uid\Uuid;

class SubcategoryGetApiTest extends ApiTestCase
{
    private SubcategoryRepository $subcategoryRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->subcategoryRepository = self::getContainer()->get(SubcategoryRepository::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function testGetOwnSubcategoryReturns200Ok(): void
    {
        $user = $this->createUser('test@user.com', 'password');
        $subcategory = new Subcategory($user, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->subcategoryRepository->save($subcategory);

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/subcategories/' . $subcategory->getId());

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $subcategory->getId()->__toString(),
            'name' => 'Groceries',
            'type' => 'expense',
            'mainCategory' => 'Food',
        ]);
    }

    public function testGetAnotherUserSubcategoryReturns404NotFound(): void
    {
        $owner = $this->createUser('owner@user.com', 'password');
        $subcategory = new Subcategory($owner, 'Groceries', TransactionType::EXPENSE, MainCategory::FOOD);
        $this->subcategoryRepository->save($subcategory);

        $attacker = $this->createUser('attacker@user.com', 'password');

        $client = $this->createClientWithCredentials($attacker);
        $client->request('GET', '/api/subcategories/' . $subcategory->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentSubcategoryReturns404NotFound(): void
    {
        $user = $this->createUser('test@user.com', 'password');

        $client = $this->createClientWithCredentials($user);
        $client->request('GET', '/api/subcategories/' . Uuid::v7()->__toString());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetSubcategoryWithoutAuthenticationReturns401Unauthorized(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/subcategories/' . Uuid::v7()->__toString());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetSubcategoryWithInvalidUuidReturns404NotFound(): void
    {
        $user = $this->createUser('test@user.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('GET', '/api/subcategories/invalid-uuid');

        $this->assertResponseStatusCodeSame(404);
    }
}
