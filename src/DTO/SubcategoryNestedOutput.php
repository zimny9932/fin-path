<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Uid\Uuid;

final readonly class SubcategoryNestedOutput
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
