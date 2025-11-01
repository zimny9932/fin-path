<?php

declare(strict_types=1);

namespace App\Serializer;

use App\DTO\SubcategoryOutput;
use App\Entity\Subcategory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SubcategoryNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer
    ) {
    }

    /**
     * @param Subcategory $object
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return [
            'id' => $object->getId()->toRfc4122(),
            'name' => $object->getName(),
            'type' => $object->getType()->value,
            'mainCategory' => $object->getMainCategory()->value,
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
