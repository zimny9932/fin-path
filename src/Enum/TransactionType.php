<?php

declare(strict_types=1);

namespace App\Enum;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/enums/transaction-types',
            provider: TransactionType::class . '::getCases',
        ),
    ],
    normalizationContext: ['groups' => ['read']]
)]
enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

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

    public static function getCases(): array
    {
        return self::cases();
    }
}
