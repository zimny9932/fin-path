<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class PaginationDetails
{
    public function __construct(
        public int $currentPage,
        public int $totalPages,
        public int $totalItems,
    ) {
    }
}
