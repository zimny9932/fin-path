<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\SubcategoryInUseException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SubcategoryInUseExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof SubcategoryInUseException) {
            return;
        }

        $responseData = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10.4.10',
            'title' => 'An error occurred',
            'detail' => $exception->getMessage(),
        ];

        $response = new JsonResponse($responseData, Response::HTTP_CONFLICT);
        $event->setResponse($response);
    }
}
