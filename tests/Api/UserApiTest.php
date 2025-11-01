<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserApiTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        static::$alwaysBootKernel = false;
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    public function testGetMeUnauthorized(): void
    {
        static::createClient()->request('GET', '/api/users/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetMeSuccessfully(): void
    {
        $client = static::createClient();
        $passwordHasher = static::getContainer()->get('security.password_hasher');

        $user = new User('test@example.com', 'dummy-password');
        $hashedPassword = $passwordHasher->hashPassword($user, 'password');
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $response = $client->request(
            'POST',
            '/api/login',
            [
                'json' => [
                    'email' => 'test@example.com',
                    'password' => 'password',
                ],
            ]
        );

        $data = $response->toArray();
        $token = $data['token'];

        $client->request('GET', '/api/users/me', ['auth_bearer' => $token]);

        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'email' => 'test@example.com',
        ]);
    }
}
