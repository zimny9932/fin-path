<?php

declare(strict_types=1);

namespace App\Exception;

final class OnboardingAlreadyCompletedException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Onboarding has already been completed.');
    }
}
