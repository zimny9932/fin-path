<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Uid\Uuid;

final readonly class TransactionOutput
{
    public function __construct(
        public Uuid $id,
        public MoneyOutput $amount,
        public \DateTimeImmutable $date,
        public ?string $description,
        public SubcategoryNestedOutput $subcategory,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => [
                'amount' => $this->amount->amount,
                'currency' => $this->amount->currency
            ],
            'date' => $this->date->format('Y-m-d'),
            'description' => $this->description,
            'subcategory' => [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                'mainCategory' => $this->subcategory->mainCategory->value,
                'type' => $this->subcategory->type->value,
            ]
        ];
    }
}
