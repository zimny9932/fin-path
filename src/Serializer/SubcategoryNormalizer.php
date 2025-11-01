<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Subcategory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class SubcategoryNormalizer implements NormalizerInterface
{
    /**
     * @param Subcategory $data
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        return [
            'id' => $data->getId()->toRfc4122(),
            'name' => $data->getName(),
            'type' => $data->getType()->value,
            'mainCategory' => $data->getMainCategory()->value,
        ];
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
