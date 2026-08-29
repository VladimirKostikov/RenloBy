<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\MetroStationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/metro-stations')]
class MetroStationController extends AbstractController
{
    public function __construct(
        private readonly MetroStationService $metroStationService,
    ) {
    }

    #[Route('', name: 'api_metro_stations_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $cityId = $request->query->get('cityId');

        return $this->json($this->metroStationService->list(
            is_numeric($cityId) ? (int) $cityId : null
        ));
    }

    #[Route('/{id}', name: 'api_metro_stations_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->metroStationService->get($id));
    }
}
