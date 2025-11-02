<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use Symfony\Component\Uid\Uuid;

final readonly class SubcategoryOutput
{
    public function __construct(
        public Uuid $id,
        public string $name,
        public TransactionType $type,
        public MainCategory $mainCategory
    ) {
    }

    public function toArray(): array{
        return [
            'id' => $this->id->toRfc4122(),
            'name' => $this->name,
            'type' => $this->type->value,
            'mainCategory' => $this->mainCategory->value,
        ];
    }
}
