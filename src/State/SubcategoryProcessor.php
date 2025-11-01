<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\SubcategoryInput;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\MainCategory;
use App\Enum\TransactionType;
use App\Exception\SubcategoryAlreadyExistsException;
use App\Repository\SubcategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class SubcategoryProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private SubcategoryRepository $subcategoryRepository,
        private Security $security,
    ) {
    }

    /**
     * @param SubcategoryInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Subcategory
    {
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (null === $user) {
            throw new UnauthorizedHttpException('Subcategory resource cannot be added without user');
        }

        if ($this->subcategoryRepository->findOneByNameAndUser($data->name, $user)) {
            throw new SubcategoryAlreadyExistsException();
        }

        $subcategory = new Subcategory(
            $user,
            $data->name,
            TransactionType::from($data->type),
            MainCategory::from($data->mainCategory)
        );

        return $this->persistProcessor->process($subcategory, $operation, $uriVariables, $context);
    }
}
