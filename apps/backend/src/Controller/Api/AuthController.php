<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\AccountService;
use App\Service\AuthCookieService;
use App\Service\AuthRateLimitService;
use App\Service\AuthService;
use App\Service\GuestCollectionMergeService;
use App\Service\GuestSessionCookieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AccountService $accountService,
        private readonly AuthCookieService $authCookieService,
        private readonly GuestSessionCookieService $guestSessionCookieService,
        private readonly GuestCollectionMergeService $guestCollectionMergeService,
        private readonly AuthRateLimitService $authRateLimitService,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Login is handled by the security firewall.');
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp();
        $this->authRateLimitService->assertRegisterAllowed(
            $clientIp !== null && $clientIp !== '' ? $clientIp : 'unknown'
        );

        $dto = $this->requestMapper->mapRegisterRequest($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        $user = $this->authService->register($dto);

        $guestSessionHash = $this->guestSessionCookieService->resolveFromRequest($request);
        if ($guestSessionHash !== null) {
            $this->guestCollectionMergeService->mergeIntoUser($user, $guestSessionHash);
        }

        $authToken = $this->authCookieService->createToken($user);
        $response = new JsonResponse($this->authService->buildUserResponse($user), Response::HTTP_CREATED);
        $this->authCookieService->attachToken($response, $authToken);

        return $response;
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $this->authCookieService->clearToken($response);

        return $response;
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->authService->buildUserResponse($user));
    }

    #[Route('/me', name: 'api_auth_me_update', methods: ['PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function updateProfile(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $dto = $this->requestMapper->mapUpdateProfile($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->json($this->accountService->updateProfile($user, $dto));
    }

    #[Route('/me/photo', name: 'api_auth_me_photo', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadPhoto(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new ValidationException(['file' => 'validation.failed']);
        }

        return $this->json($this->accountService->uploadPhoto($user, $file));
    }

    private function assertValid(object $dto): void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return;
        }

        $fields = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $property = (string) $violation->getPropertyPath();
            if ($property !== '' && !isset($fields[$property])) {
                $fields[$property] = (string) $violation->getMessage();
            }
        }

        throw new ValidationException($fields);
    }
}
