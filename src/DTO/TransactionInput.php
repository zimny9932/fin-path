<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class TransactionInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public ?string $subcategoryId = null;

    #[Assert\NotBlank]
    #[Assert\Valid]
    public ?MoneyInput $amount = null;

    #[Assert\NotBlank]
    #[Assert\Date]
    public ?string $date = null;

    #[Assert\Length(max: 200)]
    public ?string $description = null;

    public function __construct()
    {
        $this->amount = new MoneyInput();
    }
}
