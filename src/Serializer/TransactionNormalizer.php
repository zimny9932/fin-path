<?php

declare(strict_types=1);

namespace App\Serializer;

use App\DTO\MoneyOutput;
use App\DTO\SubcategoryNestedOutput;
use App\DTO\TransactionOutput;
use App\Entity\Transaction;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem('serializer.normalizer', priority: -880)]
final class TransactionNormalizer implements NormalizerInterface
{
    /**
     * @param Transaction $data
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return (new TransactionOutput(
            $data->getId(),
            new MoneyOutput($data->getAmount()->amount, $data->getAmount()->currency),
            $data->getDate(),
            $data->getDescription(),
            new SubcategoryNestedOutput($data->getSubcategory()->getId(), $data->getSubcategory()->getName()),
        ))->toArray();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Transaction;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Transaction::class => true,
        ];
    }
}
