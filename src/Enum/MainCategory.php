<?php

declare(strict_types=1);

namespace App\Enum;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/enums/main-categories',
            provider: MainCategory::class . '::getCases',
        ),
    ],
    normalizationContext: ['groups' => ['read']]
)]
enum MainCategory: string
{
    // Expenses
    case FOOD = 'Food';
    case TRANSPORT = 'Transport';
    case HOUSING = 'Housing';
    case HEALTHCARE = 'Healthcare';
    case PERSONAL_SPENDING = 'Personal Spending';
    case ENTERTAINMENT = 'Entertainment';
    case OBLIGATIONS = 'Obligations';
    case SAVINGS_AND_INVESTMENTS = 'Savings and Investments';
    case OTHER = 'Other';

    // Incomes
    //Todo
    case SALARY = 'Salary';
    case BUSINESS = 'Business';
    case INVESTMENT_INCOME = 'Investment Income';
    case GIFTS = 'Gifts';

    #[Groups(['read'])]
    public function getName(): string
    {
        return $this->name;
    }

    #[Groups(['read'])]
    public function getValue(): string
    {
        return $this->value;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getCases(): array
    {
        return self::cases();
    }

}
