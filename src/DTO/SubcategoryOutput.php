<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SubcategoryOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $mainCategory
    ) {
    }
}
