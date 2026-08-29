<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\CityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cities')]
class CityController extends AbstractController
{
    public function __construct(
        private readonly CityService $cityService,
    ) {
    }

    #[Route('', name: 'api_cities_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->cityService->list());
    }

    #[Route('/{id}', name: 'api_cities_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->cityService->get($id));
    }
}
