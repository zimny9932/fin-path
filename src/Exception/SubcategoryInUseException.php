<?php

declare(strict_types=1);

namespace App\Exception;

class SubcategoryInUseException extends \RuntimeException
{
    public function __construct(
        string $message = 'This subcategory cannot be deleted because it is associated with existing transactions.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
