<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\TransactionTotalsOutput;
use App\Entity\Subcategory;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
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

    /**
     * @return array<int, array{mainCategory: string, total: int, currency: string}>
     */
    public function findSpendingByCategory(User $user, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('main_category', 'mainCategory');
        $rsm->addScalarResult('total', 'total', 'integer');
        $rsm->addScalarResult('currency', 'currency');

        $sql = <<<SQL
SELECT
    s.main_category,
    SUM((t.amount->>'amount')::INTEGER) as total,
    t.amount->>'currency' as currency
FROM transactions t
JOIN subcategories s on t.subcategory_id = s.id
WHERE t.user_id = :userId
  AND s.type = :type
  AND t.date BETWEEN :startDate AND :endDate
GROUP BY s.main_category, currency
ORDER BY total DESC
SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $user->getId());
        $query->setParameter('type', TransactionType::EXPENSE->value);
        $query->setParameter('startDate', $startDate->format('Y-m-d'));
        $query->setParameter('endDate', $endDate->format('Y-m-d'));

        return $query->getResult();
    }

    public function hasTransactionsForSubcategory(Subcategory $subcategory): bool
    {
        return $this->count(['subcategory' => $subcategory]) > 0;
    }

    public function getTotalsInDateRange(
        User $user,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): TransactionTotalsOutput {
        $sql = <<<SQL
    SELECT
    SUM(CASE WHEN s.type = 'income' THEN (t.amount->>'amount')::INTEGER ELSE 0 END) AS total_income,
    SUM(CASE WHEN s.type = 'expense' THEN (t.amount->>'amount')::INTEGER ELSE 0 END) AS total_expenses,
    t.amount->>'currency' AS currency
FROM transactions t
JOIN subcategories s ON t.subcategory_id = s.id
WHERE t.user_id = :userId
  AND t.date BETWEEN :startDate AND :endDate
GROUP BY currency
SQL;

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total_income', 'total_income');
        $rsm->addScalarResult('total_expenses', 'total_expenses');
        $rsm->addScalarResult('currency', 'currency');

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('userId', $user->getId());
        $query->setParameter('startDate', $startDate->format('Y-m-d'));
        $query->setParameter('endDate', $endDate->format('Y-m-d'));

        $results = $query->getOneOrNullResult() ?? ['total_income' => 0, 'total_expenses' => 0, 'currency' => 'PLN'];

        return new TransactionTotalsOutput(
            Money::fromPrimitives((int) $results['total_income'], $results['currency']),
            Money::fromPrimitives((int) $results['total_expenses'], $results['currency'])
        );
    }
}
