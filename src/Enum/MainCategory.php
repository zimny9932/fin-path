<?php

declare(strict_types=1);

namespace App\Enum;

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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
