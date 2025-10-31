<?php

declare(strict_types=1);

namespace App\DTO;

final class RegistrationOutput
{
    public function __construct(
        public readonly string $token,
        public readonly string $refreshToken,
    ) {
    }
}

