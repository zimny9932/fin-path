<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    protected function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    protected function createUser(string $email, string $password): User
    {
        $user = new User($email, '');
        $hashedPassword = self::getContainer()->get('security.password_hasher')->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);

        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createClientWithCredentials(User $user): Client
    {
        return static::createClient([], ['headers' => ['authorization' => 'Bearer '.$this->getToken($user)]]);
    }

    private function getToken(User $user): string
    {
        $response = static::createClient()->request('POST', '/api/login', [
            'json' => [
                'email' => $user->getEmail(),
                'password' => 'password',
            ],
        ]);

        return $response->toArray()['token'];
    }
}
