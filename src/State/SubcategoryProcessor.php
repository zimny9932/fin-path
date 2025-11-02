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
use App\Exception\SubcategoryInUseException;
use App\Repository\SubcategoryRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Put;

final readonly class SubcategoryProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
        private SubcategoryRepository $subcategoryRepository,
        private TransactionRepository $transactionRepository,
        private Security $security,
    ) {
    }

    /**
     * @param Subcategory|SubcategoryInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof DeleteOperationInterface) {
            /** @var Subcategory $data */
            if ($this->transactionRepository->hasTransactionsForSubcategory($data)) {
                throw new SubcategoryInUseException();
            }

            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var SubcategoryInput $data */
        /** @var User|null $user */
        $user = $this->security->getUser();
        if (null === $user) {
            throw new UnauthorizedHttpException('Subcategory resource cannot be added without user');
        }

        if ($operation instanceof Put) {
            /** @var Subcategory $subcategory */
            $subcategory = $context['previous_data'];
            if ($subcategory->getName() !== $data->name && $this->subcategoryRepository->findOneByNameAndUser($data->name, $user)) {
                throw new SubcategoryAlreadyExistsException();
            }
            $subcategory->setName($data->name);
            $subcategory->setType(TransactionType::from($data->type));
            $subcategory->setMainCategory(MainCategory::from($data->mainCategory));

            return $this->persistProcessor->process($subcategory, $operation, $uriVariables, $context);
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
