<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\AuthCookieService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AuthCookieRefreshSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthCookieService $authCookieService,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api') && !str_starts_with($path, '/admin')) {
            return;
        }

        if ($path === '/api/auth/logout') {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $token = $request->cookies->get(AuthCookieService::COOKIE_NAME);
        if (!is_string($token) || $token === '') {
            return;
        }

        if (!$this->authCookieService->shouldRefresh($token)) {
            return;
        }

        $freshToken = $this->authCookieService->createToken($user);
        $this->authCookieService->attachToken($event->getResponse(), $freshToken);
    }
}
