<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\DistrictService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/districts')]
class DistrictController extends AbstractController
{
    public function __construct(
        private readonly DistrictService $districtService,
    ) {
    }

    #[Route('', name: 'api_districts_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $cityId = $request->query->get('cityId');

        return $this->json($this->districtService->list(
            is_numeric($cityId) ? (int) $cityId : null
        ));
    }

    #[Route('/{id}', name: 'api_districts_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->districtService->get($id));
    }
}
