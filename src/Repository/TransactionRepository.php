<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Subcategory;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function hasTransactionsForSubcategory(Subcategory $subcategory): bool
    {
        return null !== $this->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.subcategory = :subcategory')
            ->setParameter('subcategory', $subcategory)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
