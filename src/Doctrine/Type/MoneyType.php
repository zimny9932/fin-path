<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Model\ValueObject\Money;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

final class MoneyType extends JsonType
{
    public const NAME = 'money';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Money) {
            // Here you can handle the error, for example, by throwing an exception
            throw new \InvalidArgumentException('Value must be an instance of Money.');
        }

        $data = [
            'amount' => $value->amount,
            'currency' => $value->currency,
        ];

        return parent::convertToDatabaseValue($data, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }

        $data = parent::convertToPHPValue($value, $platform);

        if (!isset($data['amount'], $data['currency'])) {
            // Handle error for malformed data
            throw new \InvalidArgumentException('Malformed Money data.');
        }

        return Money::fromPrimitives($data['amount'], $data['currency']);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
