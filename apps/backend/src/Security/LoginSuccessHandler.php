<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Http\ApiErrorCode;
use App\Service\AuthCookieService;
use App\Service\AuthService;
use App\Service\GuestCollectionMergeService;
use App\Service\GuestSessionCookieService;
use App\Service\UserPresenceService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly AuthCookieService $authCookieService,
        private readonly AuthService $authService,
        private readonly GuestSessionCookieService $guestSessionCookieService,
        private readonly GuestCollectionMergeService $guestCollectionMergeService,
        private readonly UserPresenceService $userPresenceService,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => ApiErrorCode::AUTH_INVALID_USER], Response::HTTP_UNAUTHORIZED);
        }

        $guestSessionHash = $this->guestSessionCookieService->resolveFromRequest($request);
        if ($guestSessionHash !== null) {
            $this->guestCollectionMergeService->mergeIntoUser($user, $guestSessionHash);
        }

        $this->userPresenceService->touch($user, true);

        $authToken = $this->authCookieService->createToken($user);
        $response = new JsonResponse($this->authService->buildUserResponse($user));
        $this->authCookieService->attachToken($response, $authToken);

        return $response;
    }
}
