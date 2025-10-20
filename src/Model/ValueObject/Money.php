<?php

declare(strict_types=1);

namespace App\Model\ValueObject;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class Money
{
    private const DEFAULT_CURRENCY = 'PLN';
    private const PRECISION = 2;

    private function __construct(
        public int $amount,
        public string $currency,
    ) {
    }

    public static function fromPrimitives(int $amount, string $currency): self
    {
        return new self($amount, $currency);
    }

    public static function fromString(string $amount, ?string $currency = null): self
    {
        return new self(
            (int) ((BigDecimal::of($amount))->withScale(self::PRECISION, RoundingMode::HALF_UP)
                ->toBigDecimal()
                ->getUnscaledValue()
                ->toInt()),
            $currency ?? self::DEFAULT_CURRENCY
        );
    }

    public static function zero(): self
    {
        return new self(0, self::DEFAULT_CURRENCY);
    }
}
