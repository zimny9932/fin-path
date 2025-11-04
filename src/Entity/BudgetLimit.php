<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\ValueObject\Money;
use App\Repository\BudgetLimitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BudgetLimitRepository::class)]
#[ORM\Table(name: 'budget_limits')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_budget_limit_budget_subcategory', columns: ['budget_id', 'subcategory_id'])]
class BudgetLimit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;
    
    #[ORM\ManyToOne(targetEntity: Budget::class, inversedBy: 'budgetLimits', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'budget_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Budget $budget;

    #[ORM\ManyToOne(targetEntity: Subcategory::class)]
    #[ORM\JoinColumn(name: 'subcategory_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Subcategory $subcategory;
    
    #[ORM\Column(name: 'limit_amount', type: 'money', options: ['jsonb' => true])]
    private Money $limitAmount;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Budget $budget, Subcategory $subcategory, Money $limitAmount)
    {
        $this->id = Uuid::v7();
        $this->budget = $budget;
        $this->subcategory = $subcategory;
        $this->limitAmount = $limitAmount;
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

    public function getBudget(): Budget
    {
        return $this->budget;
    }

    public function getSubcategory(): Subcategory
    {
        return $this->subcategory;
    }

    public function getLimitAmount(): Money
    {
        return $this->limitAmount;
    }
}
