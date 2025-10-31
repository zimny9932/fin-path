<?php

declare(strict_types=1);

namespace App\Exception;

class UserAlreadyExistsException extends \Exception
{
    public function __construct(string $message = 'User with this email already exists.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

