<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\AuthRateLimitService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AuthRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthRateLimitService $authRateLimitService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== 'POST' || $request->getPathInfo() !== '/api/auth/login') {
            return;
        }

        $this->authRateLimitService->assertLoginAllowed($this->clientKey($request->getClientIp()));
    }

    private function clientKey(?string $ip): string
    {
        return $ip !== null && $ip !== '' ? $ip : 'unknown';
    }
}
