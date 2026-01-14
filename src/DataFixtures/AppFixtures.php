<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Budget;
use App\Entity\BudgetLimit;
use App\Entity\Subcategory;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Model\ValueObject\Money;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->createUser($manager);
        $this->createSubcategories($manager, $user);
        $this->createBudgets($manager, $user);
        $this->createTransactions($manager, $user);

        $manager->flush();
    }

    private function createUser(ObjectManager $manager): User
    {
        $user = new User('user@example.com', 'placeholder');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
        $user->setPasswordHash($hashedPassword);
        $user->setBillingCycleStartDay(1);

        $manager->persist($user);

        return $user;
    }

    private function createSubcategories(ObjectManager $manager, User $user): void
    {
        $subcategoriesData = [
            ['name' => 'Wynagrodzenie', 'type' => TransactionType::INCOME, 'main' => MainCategory::SALARY],
            ['name' => 'Premia', 'type' => TransactionType::INCOME, 'main' => MainCategory::SALARY],
            ['name' => 'Czynsz', 'type' => TransactionType::EXPENSE, 'main' => MainCategory::HOUSING],
            ['name' => 'Prąd', 'type' => TransactionType::EXPENSE, 'main' => MainCategory::HOUSING],
            ['name' => 'Woda', 'type' => TransactionType::EXPENSE, 'main' => MainCategory::HOUSING],
            ['name' => 'Jedzenie', 'type' => TransactionType::EXPENSE, 'main' => MainCategory::FOOD],
            ['name' => 'Transport', 'type' => TransactionType::EXPENSE, 'main' => MainCategory::TRANSPORT],
            ['name' => 'Rozrywka', 'type' => TransactionType::EXPENSE, 'main' => MainCategory::ENTERTAINMENT],
        ];

        foreach ($subcategoriesData as $data) {
            $subcategory = new Subcategory(
                $user,
                $data['name'],
                $data['type'],
                $data['main']
            );
            $manager->persist($subcategory);
            $this->addReference('subcategory_' . $data['name'], $subcategory);
        }
    }

    private function createBudgets(ObjectManager $manager, User $user): void
    {
        $today = new DateTimeImmutable('first day of this month');

        $budget = new Budget(
            $user,
            (int) $today->format('Y'),
            (int) $today->format('m'),
            Money::fromString('7000.00')
        );
        $manager->persist($budget);

        $limitsData = [
            ['subcategory' => 'Jedzenie', 'amount' => '1500.00'],
            ['subcategory' => 'Transport', 'amount' => '300.00'],
            ['subcategory' => 'Rozrywka', 'amount' => '400.00'],
        ];

        foreach ($limitsData as $data) {
            /** @var Subcategory $subcategory */
            $subcategory = $this->getReference('subcategory_' . $data['subcategory'], Subcategory::class);

            $limit = new BudgetLimit(
                $budget,
                $subcategory,
                Money::fromString($data['amount'])
            );
            $manager->persist($limit);
        }
    }

    private function createTransactions(ObjectManager $manager, User $user): void
    {
        $today = new DateTimeImmutable();

        $transactionsData = [
            [
                'description' => 'Wynagrodzenie',
                'amount' => '6000.00',
                'subcategory' => 'Wynagrodzenie',
                'type' => TransactionType::INCOME,
                'date' => $today->modify('first day of this month'),
            ],
            [
                'description' => 'Czynsz za mieszkanie',
                'amount' => '2500.00',
                'subcategory' => 'Czynsz',
                'type' => TransactionType::EXPENSE,
                'date' => $today->modify('first day of this month +2 days'),
            ],
            [
                'description' => 'Zakupy spożywcze',
                'amount' => '350.00',
                'subcategory' => 'Jedzenie',
                'type' => TransactionType::EXPENSE,
                'date' => $today->modify('first day of this month +5 days'),
            ],
            [
                'description' => 'Bilet miesięczny',
                'amount' => '120.00',
                'subcategory' => 'Transport',
                'type' => TransactionType::EXPENSE,
                'date' => $today->modify('first day of this month +7 days'),
            ],
            [
                'description' => 'Kino',
                'amount' => '50.00',
                'subcategory' => 'Rozrywka',
                'type' => TransactionType::EXPENSE,
                'date' => $today->modify('first day of this month +10 days'),
            ],
        ];

        foreach ($transactionsData as $data) {
            /** @var Subcategory $subcategory */
            $subcategory = $this->getReference('subcategory_' . $data['subcategory'], Subcategory::class);

            $transaction = new Transaction(
                $user,
                $subcategory,
                Money::fromString($data['amount']),
                $data['date'],
                $data['description']
            );

            $manager->persist($transaction);
        }
    }
}
