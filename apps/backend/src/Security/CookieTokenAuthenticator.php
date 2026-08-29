<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthCookieService;
use App\Service\UserPresenceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class CookieTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AuthCookieService $authCookieService,
        private readonly UserRepository $userRepository,
        private readonly UserPresenceService $userPresenceService,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($request->getPathInfo() === '/api/auth/login') {
            return false;
        }

        if (!str_starts_with($request->getPathInfo(), '/api')
            && !str_starts_with($request->getPathInfo(), '/admin')) {
            return false;
        }

        return $request->cookies->has(AuthCookieService::COOKIE_NAME);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->cookies->get(AuthCookieService::COOKIE_NAME);
        if (!is_string($token) || $token === '') {
            throw new AuthenticationException('Missing auth token');
        }

        $userId = $this->authCookieService->resolveUserId($token);
        if ($userId === null) {
            throw new AuthenticationException('Invalid auth token');
        }

        return new SelfValidatingPassport(
            new UserBadge((string) $userId, function (string $id): User {
                $user = $this->userRepository->find((int) $id);
                if (!$user instanceof User || $user->isDeleted()) {
                    throw new AuthenticationException('User not found');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->userPresenceService->touch($user);
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
