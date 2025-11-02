<?php

declare(strict_types=1);

namespace App\DTO;

use App\Model\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

final readonly class BudgetLimitOutput
{
    public function __construct(
        public Uuid $id,
        public Money $limitAmount,
        public SubcategoryNestedOutput $subcategory,
    ) {
    }
}


