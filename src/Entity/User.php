<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\DTO\OnboardingInput;
use App\DTO\RegistrationInput;
use App\DTO\RegistrationOutput;
use App\DTO\UserOutput;
use App\Exception\OnboardingAlreadyCompletedException;
use App\Exception\UserAlreadyExistsException;
use App\Repository\UserRepository;
use App\Service\UserRegistrationProcessor;
use App\State\MeProvider;
use App\State\OnboardingProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/users/me',
            provider: MeProvider::class,
            output: UserOutput::class,
            security: "is_granted('ROLE_USER')",
        ),
        new Patch(
            uriTemplate: '/users/me/onboarding',
            security: "is_granted('ROLE_USER')",
            input: OnboardingInput::class,
            output: UserOutput::class,
            processor: OnboardingProcessor::class,
            exceptionToStatus: [OnboardingAlreadyCompletedException::class => 400],
            inputFormats: ['json' => ['application/json']],
        ),
        new Post(
            uriTemplate: '/register',
            input: RegistrationInput::class,
            output: RegistrationOutput::class,
            processor: UserRegistrationProcessor::class,
            exceptionToStatus: [UserAlreadyExistsException::class => 409]
        ),
        new Post(
            uriTemplate: '/login',
            security: "is_granted('PUBLIC_ACCESS')",
            input: false,
            output: false,
        ),
        new Post(
            uriTemplate: '/token/refresh',
            security: "is_granted('PUBLIC_ACCESS')",
            input: false,
            output: false,
        ),
    ],
    graphQlOperations: []
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('email')]
class User implements PasswordAuthenticatedUserInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: Types::STRING, length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'billing_cycle_start_day', type: Types::SMALLINT, nullable: true)]
    private ?int $billingCycleStartDay = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $email, string $passwordHash)
    {
        $this->id = Uuid::v7();
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function getBillingCycleStartDay(): ?int
    {
        return $this->billingCycleStartDay;
    }

    public function setBillingCycleStartDay(?int $billingCycleStartDay): void
    {
        $this->billingCycleStartDay = $billingCycleStartDay;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // e.g. plain passwords
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
