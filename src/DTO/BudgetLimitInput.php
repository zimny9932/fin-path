<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class BudgetLimitInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $subcategoryId = null;

    #[Assert\Valid]
    #[Assert\NotBlank]
    public ?MoneyInput $limitAmount = null;
}
