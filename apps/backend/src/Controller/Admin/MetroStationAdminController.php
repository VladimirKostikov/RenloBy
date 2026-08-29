<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\RequestMapper;
use App\Service\MetroStationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/metro-stations')]
#[IsGranted('ROLE_ADMIN')]
class MetroStationAdminController extends AbstractController
{
    public function __construct(
        private readonly MetroStationService $metroStationService,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    #[Route('', name: 'admin_metro_stations_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->metroStationService->list());
    }

    #[Route('/{id}', name: 'admin_metro_stations_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->metroStationService->get($id));
    }

    #[Route('', name: 'admin_metro_stations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $station = $this->metroStationService->create(
            $this->requestMapper->mapCreateMetroStation($this->requestMapper->decodeJson($request))
        );

        return $this->json($station, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_metro_stations_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        return $this->json(
            $this->metroStationService->update($id, $this->requestMapper->mapUpdateMetroStation($this->requestMapper->decodeJson($request)))
        );
    }

    #[Route('/{id}', name: 'admin_metro_stations_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $this->metroStationService->delete($id);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
