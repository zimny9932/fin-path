<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\MainCategory;
use App\Enum\TransactionType;
use Symfony\Component\Validator\Constraints as Assert;

final class SubcategoryInput
{
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Subcategory name must be at least {{ limit }} characters long',
        maxMessage: 'Subcategory name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [TransactionType::class, 'values'])]
    public string $type;

    #[Assert\NotBlank]
    #[Assert\Choice(callback: [MainCategory::class, 'values'])]
    public string $mainCategory;
}
