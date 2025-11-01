<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class RegistrationApiTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testSuccessfulRegistration(): void
    {
        $response = static::createClient()->request('POST', '/api/register', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email' => 'test@example.com',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ],
        ]);

        self::assertResponseStatusCodeSame(201);

        $data = $response->toArray();
        self::assertArrayHasKey('token', $data);
        self::assertArrayHasKey('refreshToken', $data);

        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        self::assertNotNull($user);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testRegistrationWithExistingEmail(): void
    {
        // We need a password hasher to create a valid user
        $passwordHasher = static::getContainer()->get('security.password_hasher');

        $existingUser = new User('existing@example.com', 'dummy-password');
        $hashedPassword = $passwordHasher->hashPassword($existingUser, 'password');
        $existingUser->setPasswordHash($hashedPassword);

        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();

        static::createClient()->request('POST', '/api/register', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'email' => 'existing@example.com',
                'password' => 'password123',
                'passwordConfirmation' => 'password123',
            ],
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[DataProvider('provideInvalidData')]
    public function testRegistrationValidationErrors(array $payload, string $expectedErrorMessage): void
    {
        static::createClient()->request('POST', '/api/register', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertJsonContains([
            'violations' => [
                ['message' => $expectedErrorMessage]
            ]
        ]);
    }

    public static function provideInvalidData(): \Generator
    {
        yield 'blank email' => [
            'payload' => ['email' => '', 'password' => 'password123', 'passwordConfirmation' => 'password123'],
            'expectedErrorMessage' => 'This value should not be blank.',
        ];
        yield 'invalid email' => [
            'payload' => ['email' => 'invalid-email', 'password' => 'password123', 'passwordConfirmation' => 'password123'],
            'expectedErrorMessage' => 'This value is not a valid email address.',
        ];
        yield 'short password' => [
            'payload' => ['email' => 'test@example.com', 'password' => '1234', 'passwordConfirmation' => '1234'],
            'expectedErrorMessage' => 'Password should be at least 8 characters',
        ];
        yield 'passwords do not match' => [
            'payload' => ['email' => 'test@example.com', 'password' => 'password123', 'passwordConfirmation' => 'password456'],
            'expectedErrorMessage' => 'Passwords do not match',
        ];
    }
}
