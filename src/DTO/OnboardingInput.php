<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class OnboardingInput
{
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 31)]
    public mixed $billingCycleStartDay;
}
