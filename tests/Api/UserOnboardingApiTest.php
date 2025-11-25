<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\User;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserOnboardingApiTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = self::getContainer()->get('security.password_hasher');
    }

    public function testOnboardingSetsBillingCycleStartDay(): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $response = $client->request('PATCH', '/api/users/me/onboarding', [
            'json' => ['billingCycleStartDay' => 15],
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals(15, $data['billingCycleStartDay']);

        $this->entityManager->clear();
        $updatedUser = $this->entityManager->find(User::class, $user->getId());
        $this->assertEquals(15, $updatedUser->getBillingCycleStartDay());
    }

    public function testOnboardingFailsIfAlreadyCompleted(): void
    {
        $user = $this->createUser('user@example.com', 'password', 10);
        $client = $this->createClientWithCredentials($user);

        $client->request('PATCH', '/api/users/me/onboarding', [
            'json' => ['billingCycleStartDay' => 20],
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testOnboardingFailsForUnauthenticatedUser(): void
    {
        static::createClient()->request('PATCH', '/api/users/me/onboarding', [
            'json' => ['billingCycleStartDay' => 25],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    #[DataProvider('invalidDataProvider')]
    public function testOnboardingFailsWithInvalidData(mixed $day, string $expectedMessage): void
    {
        $user = $this->createUser('user@example.com', 'password');
        $client = $this->createClientWithCredentials($user);

        $client->request('PATCH', '/api/users/me/onboarding', [
            'json' => ['billingCycleStartDay' => $day],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains([
            'violations' => [
                ['message' => $expectedMessage]
            ]
        ]);
    }

    public static function invalidDataProvider(): \Generator
    {
        yield 'too low' => [0, 'This value should be between 1 and 31.'];
        yield 'too high' => [32, 'This value should be between 1 and 31.'];
        yield 'not a number' => ['abc', 'This value should be a valid number.'];
        yield 'null' => [null, 'This value should not be blank.'];
    }
}
