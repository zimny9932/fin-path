<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CopyBudgetInput
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    public int $sourceYear;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Range(min: 1, max: 12)]
    public int $sourceMonth;
}
