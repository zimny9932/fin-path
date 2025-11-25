<?php

declare(strict_types=1);

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Subcategory;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Budget;
use App\Entity\BudgetLimit;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use Doctrine\ORM\EntityManagerInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    protected function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }

    protected function createUser(string $email, string $password, ?int $billingCycleStartDay = null): User
    {
        $user = new User($email, '');
        if (null !== $billingCycleStartDay){
            $user->setBillingCycleStartDay($billingCycleStartDay);
        }
        $hashedPassword = self::getContainer()->get('security.password_hasher')->hashPassword($user, $password);
        $user->setPasswordHash($hashedPassword);

        $em = $this->entityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createClientWithCredentials(User $user): Client
    {
        return static::createClient([], ['headers' => ['authorization' => 'Bearer '.$this->getToken($user)]]);
    }

    protected function createTransaction(
        User $user,
        Subcategory $subcategory,
        Money $amount,
        \DateTimeImmutable $date,
        ?string $description = null
    ): Transaction {
        $transaction = new Transaction($user, $subcategory, $amount, $date, $description);
        $this->entityManager()->persist($transaction);
        $this->entityManager()->flush();

        return $transaction;
    }

    protected function createSubcategory(User $user, string $name, ?TransactionType $type = TransactionType::EXPENSE, ?MainCategory $mainCategory = MainCategory::FOOD): Subcategory
    {
        $em = $this->entityManager();
        /** @var User $managedUser */
        $managedUser = $em->find(User::class, $user->getId());
        $subcategory = new Subcategory($managedUser, $name, $type, $mainCategory);
        $em->persist($subcategory);
        $em->flush();

        return $subcategory;
    }

    protected function createBudget(User $user, int $year, int $month, Money $plannedIncome): Budget
    {
        $em = $this->entityManager();
        /** @var User $managedUser */
        $managedUser = $em->find(User::class, $user->getId());
        $budget = new Budget($managedUser, $year, $month, $plannedIncome);
        $em->persist($budget);
        $em->flush();

        return $budget;
    }

    protected function createBudgetLimit(Budget $budget, Subcategory $subcategory, Money $limitAmount): BudgetLimit
    {
        $budgetLimit = new BudgetLimit($budget, $subcategory, $limitAmount);
        $this->entityManager()->persist($budgetLimit);
        $this->entityManager()->flush();

        return $budgetLimit;
    }

    private function getToken(User $user): string
    {
        $response = static::createClient()->request('POST', '/api/login', [
            'json' => [
                'email' => $user->getEmail(),
                'password' => 'password',
            ],
        ]);

        return $response->toArray()['token'];
    }
}
