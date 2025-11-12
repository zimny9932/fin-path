<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\DTO\SpendingByCategoryOutput;
use App\State\SpendingByCategoryProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/reports/spending-by-category',
            security: "is_granted('ROLE_USER')",
            output: SpendingByCategoryOutput::class,
            provider: SpendingByCategoryProvider::class,
        ),
    ],
    security: "is_granted('ROLE_USER')"
)]
final class SpendingReport
{
}
