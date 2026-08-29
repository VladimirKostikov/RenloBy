<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Collection\CollectionOwner;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class CollectionRequestContext
{
    public function __construct(
        public CollectionOwner $owner,
        public bool $attachGuestCookie,
        public ?string $guestCookieToken,
    ) {
    }
}

class CollectionRequestResolver
{
    public function __construct(
        private readonly GuestSessionCookieService $guestSessionCookieService,
    ) {
    }

    public function resolve(Request $request, ?User $user): CollectionRequestContext
    {
        if ($user instanceof User) {
            return new CollectionRequestContext(
                new CollectionOwner($user, null),
                false,
                null,
            );
        }

        $session = $this->guestSessionCookieService->resolveOrCreate($request);

        return new CollectionRequestContext(
            new CollectionOwner(null, $session['hash']),
            $session['isNew'],
            $session['token'],
        );
    }

    public function applyGuestCookie(Response $response, CollectionRequestContext $context): Response
    {
        if (!$context->attachGuestCookie || $context->guestCookieToken === null) {
            return $response;
        }

        $this->guestSessionCookieService->attachToken($response, $context->guestCookieToken);

        return $response;
    }

    public function json(mixed $data, CollectionRequestContext $context, int $status = 200): JsonResponse
    {
        return $this->applyGuestCookie(new JsonResponse($data, $status), $context);
    }
}
