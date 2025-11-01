<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
