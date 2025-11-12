<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\DTO\DashboardOutput;
use App\State\DashboardProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/dashboard',
            security: 'is_granted("ROLE_USER")',
            output: DashboardOutput::class,
            provider: DashboardProvider::class,
        ),
    ],
    security: 'is_granted("ROLE_USER")',
)]
final class Dashboard
{
}

