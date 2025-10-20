<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\ValueObject\Money;
use App\Repository\BudgetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BudgetRepository::class)]
#[ORM\Table(name: 'budgets')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uq_budget_user_year_month', columns: ['user_id', 'year', 'month'])]
class Budget
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $year;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $month;

    #[ORM\Column(name: 'planned_income', type: 'money', options: ['jsonb' => true])]
    private Money $plannedIncome;

    #[ORM\OneToMany(mappedBy: 'budget', targetEntity: BudgetLimit::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $budgetLimits;

    #[ORM\Column(name: 'created_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, int $year, int $month, Money $plannedIncome)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->year = $year;
        $this->month = $month;
        $this->plannedIncome = $plannedIncome;
        $this->budgetLimits = new ArrayCollection();
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

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getPlannedIncome(): Money
    {
        return $this->plannedIncome;
    }
    
    /**
     * @return Collection<int, BudgetLimit>
     */
    public function getBudgetLimits(): Collection
    {
        return $this->budgetLimits;
    }
}
