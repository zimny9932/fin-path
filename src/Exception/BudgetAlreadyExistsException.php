<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class BudgetAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Budget for this period already exists.');
    }
}
