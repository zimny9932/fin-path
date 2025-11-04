<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\BudgetAlreadyExistsException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class BudgetAlreadyExistsExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof BudgetAlreadyExistsException) {
            return;
        }

        $responseData = [
            'message' => $exception->getMessage(),
        ];

        $response = new JsonResponse($responseData, Response::HTTP_CONFLICT);
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
