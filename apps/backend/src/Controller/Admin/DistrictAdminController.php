<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\DistrictService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/districts')]
#[IsGranted('ROLE_ADMIN')]
class DistrictAdminController extends AbstractController
{
    public function __construct(
        private readonly DistrictService $districtService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_districts_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->districtService->list());
    }

    #[Route('/{id}', name: 'admin_districts_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->districtService->get($id));
    }

    #[Route('', name: 'admin_districts_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $district = $this->districtService->create(
            $this->requestMapper->mapCreateDistrict($this->requestMapper->decodeJson($request))
        );

        return $this->json($district, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_districts_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->districtService->update($id, $this->requestMapper->mapUpdateDistrict($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_districts_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->districtService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
