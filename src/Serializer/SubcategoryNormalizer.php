<?php

declare(strict_types=1);

namespace App\Serializer;

use App\DTO\SubcategoryOutput;
use App\Entity\Subcategory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class SubcategoryNormalizer implements NormalizerInterface
{
    /**
     * @param Subcategory $data
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return (new SubcategoryOutput(
            $data->getId(),
            $data->getName(),
            $data->getType(),
            $data->getMainCategory(),
        ))->toArray();
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Subcategory;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Subcategory::class => true,
        ];
    }
}
