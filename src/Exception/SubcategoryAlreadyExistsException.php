<?php

declare(strict_types=1);

namespace App\Exception;

final class SubcategoryAlreadyExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Subcategory with the same name already exists for this user.');
    }
}
