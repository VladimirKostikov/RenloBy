<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\AiPreferenceService;
use App\Service\CollectionRequestResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/ai-preferences')]
class AiPreferenceController extends AbstractController
{
    public function __construct(
        private readonly AiPreferenceService $aiPreferenceService,
        private readonly CollectionRequestResolver $collectionRequestResolver,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/latest', name: 'api_ai_preferences_latest', methods: ['GET'])]
    public function latest(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);
        $preference = $this->aiPreferenceService->latest($context->owner);

        return $this->collectionRequestResolver->json(
            ['item' => $preference],
            $context,
        );
    }

    #[Route('', name: 'api_ai_preferences_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);
        $dto = $this->requestMapper->mapCreateAiPreference($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        $preference = $this->aiPreferenceService->create($context->owner, $dto);

        return $this->collectionRequestResolver->json($preference, $context, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_ai_preferences_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);

        return $this->collectionRequestResolver->json(
            $this->aiPreferenceService->get($context->owner, $id),
            $context,
        );
    }

    #[Route('/{id}', name: 'api_ai_preferences_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id, #[CurrentUser] ?User $user): Response
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);
        $this->aiPreferenceService->softDelete($context->owner, $id);

        return $this->collectionRequestResolver->applyGuestCookie(
            new Response('', Response::HTTP_NO_CONTENT),
            $context,
        );
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
