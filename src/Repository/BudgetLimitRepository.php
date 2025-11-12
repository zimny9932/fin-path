<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BudgetLimit;
use App\Entity\Budget;
use App\Model\ValueObject\Money;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BudgetLimit>
 */
class BudgetLimitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BudgetLimit::class);
    }

    public function getTotalPlannedExpenses(Budget $budget): Money
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total_planned', 'total_planned');
        $rsm->addScalarResult('currency', 'currency');

        $sql = <<<SQL
        SELECT
            SUM((bl.limit_amount->>'amount')::INTEGER) as total_planned,
            bl.limit_amount->>'currency' as currency
        FROM budget_limits bl
        WHERE bl.budget_id = :budgetId
        GROUP BY currency
    SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('budgetId', $budget->getId()->toRfc4122());

        $result = $query->getSingleResult();

        return Money::fromPrimitives($result['total_planned'], $result['currency']);
    }
}
