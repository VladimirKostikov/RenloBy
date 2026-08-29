<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\RequestMapper;
use App\Service\CollectionRequestResolver;
use App\Service\FavoriteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/favorites')]
class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
        private readonly CollectionRequestResolver $collectionRequestResolver,
        private readonly RequestMapper $requestMapper,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_favorites_index', methods: ['GET'])]
    public function index(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);

        return $this->collectionRequestResolver->json([
            'items' => $this->favoriteService->listWithListings($context->owner),
        ], $context);
    }

    #[Route('', name: 'api_favorites_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);
        $dto = $this->requestMapper->mapCreateFavorite($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        $favorite = $this->favoriteService->add($context->owner, $dto);

        return $this->collectionRequestResolver->json($favorite, $context, Response::HTTP_CREATED);
    }

    #[Route('/toggle', name: 'api_favorites_toggle', methods: ['POST'])]
    public function toggle(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);
        $dto = $this->requestMapper->mapCreateFavorite($this->requestMapper->decodeJson($request));
        $this->assertValid($dto);

        return $this->collectionRequestResolver->json(
            $this->favoriteService->toggle($context->owner, $dto->listingId),
            $context,
        );
    }

    #[Route('/{id}', name: 'api_favorites_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id, #[CurrentUser] ?User $user): Response
    {
        $context = $this->collectionRequestResolver->resolve($request, $user);
        $this->favoriteService->remove($context->owner, $id);

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
