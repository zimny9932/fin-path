<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\DTO\PaginatedTransactionOutput;
use App\DTO\TransactionInput;
use App\DTO\TransactionOutput;
use App\Doctrine\Type\MoneyType;
use App\Entity\Contract\UserOwnedInterface;
use App\Model\ValueObject\Money;
use App\Repository\TransactionRepository;
use App\State\TransactionProcessor;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            input: TransactionInput::class,
            output: TransactionOutput::class,
            processor: TransactionProcessor::class
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 30,
    graphQlOperations: []
)]
#[ApiFilter(DateFilter::class, properties: ['date'])]
#[ApiFilter(
    OrderFilter::class,
    properties: ['date', 'amount.amount'],
    arguments: ['orderParameterName' => 'sortOrder']
)]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
#[ORM\HasLifecycleCallbacks]
class Transaction implements UserOwnedInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Subcategory::class)]
    #[ORM\JoinColumn(name: 'subcategory_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Subcategory $subcategory;

    #[ORM\Column(type: MoneyType::NAME)]
    private Money $amount;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: Types::STRING, length: 200, nullable: true)]
    private ?string $description;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        User $user,
        Subcategory $subcategory,
        Money $amount,
        \DateTimeImmutable $date,
        ?string $description
    ) {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->subcategory = $subcategory;
        $this->amount = $amount;
        $this->date = $date;
        $this->description = $description;
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubcategory(): Subcategory
    {
        return $this->subcategory;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
