<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\CityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cities')]
#[IsGranted('ROLE_ADMIN')]
class CityAdminController extends AbstractController
{
    public function __construct(
        private readonly CityService $cityService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_cities_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->cityService->list());
    }

    #[Route('/{id}', name: 'admin_cities_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->cityService->get($id));
    }

    #[Route('', name: 'admin_cities_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $city = $this->cityService->create(
            $this->requestMapper->mapCreateCity($this->requestMapper->decodeJson($request))
        );

        return $this->json($city, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_cities_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->cityService->update($id, $this->requestMapper->mapUpdateCity($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_cities_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->cityService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
