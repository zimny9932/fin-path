<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Subcategory;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subcategory>
 */
class SubcategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subcategory::class);
    }

    public function findForUserByUserAndType(User $user, ?TransactionType $type): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.name', 'ASC');

        if ($type) {
            $qb->andWhere('s.type = :type')
                ->setParameter('type', $type);
        }

        return $qb;
    }

    public function findOneByNameAndUser(string $name, User $user): ?Subcategory
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.name = :name')
            ->setParameter('user', $user)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<Subcategory>
     */
    public function findRecentForUser(User $user, int $limit): array
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->innerJoin(Transaction::class, 't', 'WITH', 't.subcategory = s.id')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->groupBy('s.id')
            ->orderBy('MAX(t.date)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
