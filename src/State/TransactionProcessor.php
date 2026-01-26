<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\TransactionInput;
use App\Entity\Subcategory;
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
        $subcategory = $this->findSubcategoryForUser($user, $data->subcategoryId);

        if ($operation instanceof Post) {
            return $this->createTransaction($data, $user, $subcategory);
        }

        if ($operation instanceof Put) {
            $previous = $context['previous_data'] ?? null;

            if (!$previous instanceof Transaction) {
                throw new NotFoundHttpException('Transaction not found');
            }

            return $this->updateTransaction($previous, $data, $subcategory);
        }

        throw new \LogicException('This processor does not support the given operation.');
    }

    private function createTransaction(TransactionInput $data, User $user, Subcategory $subcategory): Transaction
    {
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

    private function updateTransaction(Transaction $transaction, TransactionInput $data, Subcategory $subcategory): Transaction
    {
        $transaction = $this->entityManager->getRepository(Transaction::class)->find($transaction->getId());
        $money = Money::fromPrimitives($data->amount->amount, $data->amount->currency);
        $date = new \DateTimeImmutable($data->date);

        $transaction->setSubcategory($subcategory);
        $transaction->setAmount($money);
        $transaction->setDate($date);
        $transaction->setDescription($data->description);

        $this->entityManager->flush();

        return $transaction;
    }

    private function findSubcategoryForUser(User $user, string $subcategoryId): Subcategory
    {
        $subcategory = $this->subcategoryRepository->findForUser($user, $subcategoryId);

        if (!$subcategory) {
            throw new NotFoundHttpException('Subcategory not found');
        }

        return $subcategory;
    }

    public function supports(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): bool
    {
        return $data instanceof TransactionInput;
    }
}
