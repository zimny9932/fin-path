<?php

declare(strict_types=1);

namespace App\Service;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\RegistrationInput;
use App\DTO\RegistrationOutput;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
    ) {
    }

    /**
     * @param RegistrationInput $data
     * @throws UserAlreadyExistsException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RegistrationOutput
    {
        if ($this->userRepository->findOneBy(['email' => $data->email])) {
            throw new UserAlreadyExistsException();
        }

        $user = new User($data->email, 'dummy-password');
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $token = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, 2592000);
        $this->refreshTokenManager->save($refreshToken);

        return new RegistrationOutput($token, $refreshToken->getRefreshToken());
    }
}
