<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\SubcategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<Subcategory>
 */
final readonly class RecentSubcategoriesProvider implements ProviderInterface
{
    private const DEFAULT_LIMIT = 5;

    public function __construct(
        private SubcategoryRepository $subcategoryRepository,
        private Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        }

        $limit = $this->getLimit($context);

        return $this->subcategoryRepository->findRecentForUser($user, $limit);
    }

    private function getLimit(array $context): int
    {
        $limit = $context['filters']['limit'] ?? self::DEFAULT_LIMIT;

        if (!is_numeric($limit) || (int) $limit <= 0) {
            throw new BadRequestHttpException('Limit must be a positive integer.');
        }

        return (int) $limit;
    }
}
