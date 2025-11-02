<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class MoneyInput
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public ?int $amount = null;

    #[Assert\NotBlank]
    #[Assert\Currency]
    public ?string $currency = null;
}
