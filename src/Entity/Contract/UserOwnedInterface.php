<?php

declare(strict_types=1);

namespace App\Entity\Contract;

use App\Entity\User;

interface UserOwnedInterface
{
    public function getUser(): User;
}
