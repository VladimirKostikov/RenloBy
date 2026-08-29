<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Collection\CollectionOwner;
use App\Entity\User;
use App\Http\RequestMapper;
use App\Service\ComparisonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/comparisons')]
#[IsGranted('ROLE_ADMIN')]
class ComparisonAdminController extends AbstractController
{
    public function __construct(
        private readonly ComparisonService $comparisonService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_comparisons_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $owner = new CollectionOwner($user, null);

        return $this->json($this->comparisonService->list($owner));
    }

    #[Route('', name: 'admin_comparisons_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $owner = new CollectionOwner($user, null);
        $comparison = $this->comparisonService->add(
            $owner,
            $this->requestMapper->mapCreateComparison($this->requestMapper->decodeJson($request))
        );

        return $this->json($comparison, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_comparisons_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $owner = new CollectionOwner($user, null);
        $this->comparisonService->remove($owner, $id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
