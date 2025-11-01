<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Uid\Uuid;

final readonly class UserOutput
{
    public function __construct(
        public Uuid $id,
        public string $email,
        public ?int $billingCycleStartDay,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt
    ) {
    }
}
