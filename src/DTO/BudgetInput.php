<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class BudgetInput
{
    #[Assert\NotBlank(groups: ['budget:create'])]
    #[Assert\Range(min: 2020, max: 2100)]
    public ?int $year = null;

    #[Assert\NotBlank(groups: ['budget:create'])]
    #[Assert\Range(min: 1, max: 12)]
    public ?int $month = null;

    #[Assert\Valid]
    #[Assert\NotBlank]
    public ?MoneyInput $plannedIncome = null;

    /** @var BudgetLimitInput[] */
    #[Assert\Valid]
    public array $limits = [];
}
