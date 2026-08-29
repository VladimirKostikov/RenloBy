<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Http\RequestMapper;
use App\Service\SavedSearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/saved-searches')]
#[IsGranted('ROLE_USER')]
class SavedSearchController extends AbstractController
{
    public function __construct(
        private readonly SavedSearchService $savedSearchService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'api_saved_searches_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->savedSearchService->list($user));
    }

    #[Route('/{id}', name: 'api_saved_searches_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->savedSearchService->get($user, $id));
    }

    #[Route('', name: 'api_saved_searches_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $savedSearch = $this->savedSearchService->create(
            $user,
            $this->requestMapper->mapCreateSavedSearch($this->requestMapper->decodeJson($request))
        );

        return $this->json($savedSearch, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_saved_searches_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json(
            $this->savedSearchService->update(
                $user,
                $id,
                $this->requestMapper->mapUpdateSavedSearch($this->requestMapper->decodeJson($request))
            )
        );
    }

    #[Route('/{id}', name: 'api_saved_searches_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->savedSearchService->delete($user, $id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
