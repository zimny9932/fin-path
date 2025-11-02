<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use Symfony\Component\Uid\Uuid;

final readonly class SubcategoryNestedOutput
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public MainCategory $mainCategory,
        public TransactionType $type,
    ) {
    }
}
