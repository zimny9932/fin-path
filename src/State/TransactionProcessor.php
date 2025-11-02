<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\TransactionInput;
use App\Entity\Transaction;
use App\Entity\User;
use App\Model\ValueObject\Money;
use App\Repository\SubcategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<TransactionInput, Transaction>
 */
final class TransactionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly SubcategoryRepository $subcategoryRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Transaction
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $subcategory = $this->subcategoryRepository->findForUser($user, $data->subcategoryId);

        if (!$subcategory) {
            throw new NotFoundHttpException('Subcategory not found');
        }

        $money = Money::fromPrimitives($data->amount->amount, $data->amount->currency);
        $date = new \DateTimeImmutable($data->date);

        $transaction = new Transaction(
            $user,
            $subcategory,
            $money,
            $date,
            $data->description
        );

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    public function supports(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): bool
    {
        return $data instanceof TransactionInput && $operation->getName() === '_api_/api/transactions_post';
    }
}
