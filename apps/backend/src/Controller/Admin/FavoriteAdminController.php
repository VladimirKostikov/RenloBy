<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Collection\CollectionOwner;
use App\Entity\User;
use App\Http\RequestMapper;
use App\Service\FavoriteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/favorites')]
#[IsGranted('ROLE_ADMIN')]
class FavoriteAdminController extends AbstractController
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_favorites_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $owner = new CollectionOwner($user, null);

        return $this->json($this->favoriteService->list($owner));
    }

    #[Route('', name: 'admin_favorites_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $owner = new CollectionOwner($user, null);
        $favorite = $this->favoriteService->add(
            $owner,
            $this->requestMapper->mapCreateFavorite($this->requestMapper->decodeJson($request))
        );

        return $this->json($favorite, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_favorites_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $owner = new CollectionOwner($user, null);
        $this->favoriteService->remove($owner, $id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
