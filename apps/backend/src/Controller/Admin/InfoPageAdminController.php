<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\InfoPageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/info-pages')]
#[IsGranted('ROLE_ADMIN')]
class InfoPageAdminController extends AbstractController
{
    public function __construct(
        private readonly InfoPageService $infoPageService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_info_pages_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->infoPageService->list());
    }

    #[Route('/{id}', name: 'admin_info_pages_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->infoPageService->get($id));
    }

    #[Route('', name: 'admin_info_pages_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $page = $this->infoPageService->create(
            $this->requestMapper->mapCreateInfoPage($this->requestMapper->decodeJson($request))
        );

        return $this->json($page, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_info_pages_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->infoPageService->update($id, $this->requestMapper->mapUpdateInfoPage($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_info_pages_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->infoPageService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
