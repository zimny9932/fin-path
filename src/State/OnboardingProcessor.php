<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use App\DTO\OnboardingInput;
use App\Entity\User;
use App\Exception\OnboardingAlreadyCompletedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Metadata\Operation;

final readonly class OnboardingProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    /**
     * @param OnboardingInput $data
     * @throws OnboardingAlreadyCompletedException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->getBillingCycleStartDay() !== null) {
            throw new OnboardingAlreadyCompletedException();
        }

        $user->setBillingCycleStartDay($data->billingCycleStartDay);
        $this->entityManager->flush();

        return $user;
    }
}
