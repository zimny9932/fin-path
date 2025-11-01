<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TokenRefreshApiTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testSuccessfulTokenRefresh(): void
    {
        $this->createUser('test@example.com', 'password123');

        $loginResponse = static::createClient()->request('POST', '/api/login', [
            'json' => [
                'email' => 'test@example.com',
                'password' => 'password123',
            ],
        ]);

        $refreshToken = $loginResponse->toArray()['refresh_token'];

        $refreshResponse = static::createClient()->request('POST', '/api/token/refresh', [
            'json' => [
                'refresh_token' => $refreshToken,
            ],
        ]);

        self::assertResponseStatusCodeSame(200);
        $data = $refreshResponse->toArray();
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('refresh_token', $data);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testTokenRefreshWithInvalidToken(): void
    {
        static::createClient()->request('POST', '/api/token/refresh', [
            'json' => [
                'refresh_token' => 'invalid-token',
            ],
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $user = new User($email, '');
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
