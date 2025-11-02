<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Budget;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Budget>
 */
class BudgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Budget::class);
    }

    public function findWithRelations(User $user, int $year, int $month): ?Budget
    {
        return $this->createQueryBuilder('b')
            ->addSelect('bl', 's')
            ->leftJoin('b.budgetLimits', 'bl')
            ->leftJoin('bl.subcategory', 's')
            ->andWhere('b.user = :user')
            ->andWhere('b.year = :year')
            ->andWhere('b.month = :month')
            ->setParameter('user', $user)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
