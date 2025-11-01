<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\DTO\SubcategoryOutput;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Repository\SubcategoryRepository;
use App\State\SubcategoryCollectionProvider;
use App\State\SubcategoryUserFilterProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(
            security: "is_granted('ROLE_USER')",
        ),
    ],
    graphQlOperations: []
)]
#[ApiFilter(SearchFilter::class, properties: ['type' => 'exact', 'user' => 'exact'])]
#[ORM\Entity(repositoryClass: SubcategoryRepository::class)]
#[ORM\Table(name: 'subcategories')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_subcategory_user_name', columns: ['user_id', 'name'])]
class Subcategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: TransactionType::class)]
    private TransactionType $type;

    #[ORM\Column(name: 'main_category', type: Types::STRING, length: 50, enumType: MainCategory::class)]
    private MainCategory $mainCategory;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $name, TransactionType $type, MainCategory $mainCategory)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->name = $name;
        $this->type = $type;
        $this->mainCategory = $mainCategory;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getMainCategory(): MainCategory
    {
        return $this->mainCategory;
    }
}
