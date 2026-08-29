<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Exception\ServiceUnavailableException;
use App\Exception\TooManyRequestsException;
use App\Exception\ValidationException;
use App\Http\ApiErrorCode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ResourceNotFoundException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND
            ));

            return;
        }

        if ($exception instanceof ValidationException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage(), 'fields' => $exception->fields],
                Response::HTTP_UNPROCESSABLE_ENTITY
            ));

            return;
        }

        if ($exception instanceof ConflictException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_CONFLICT
            ));

            return;
        }

        if ($exception instanceof ForbiddenException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_FORBIDDEN
            ));

            return;
        }

        if ($exception instanceof ServiceUnavailableException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE
            ));

            return;
        }

        if ($exception instanceof TooManyRequestsException) {
            $event->setResponse(new JsonResponse(
                ['error' => ApiErrorCode::AUTH_RATE_LIMITED],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) $exception->getRetryAfterSeconds()]
            ));
        }
    }
}
