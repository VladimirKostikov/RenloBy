<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enum\DealType;
use App\Service\MapStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/map')]
class MapStatsController extends AbstractController
{
    public function __construct(
        private readonly MapStatsService $mapStatsService,
    ) {
    }

    #[Route('/stats', name: 'api_map_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $dealTypeRaw = $request->query->get('dealType');
        $dealType = is_string($dealTypeRaw) && $dealTypeRaw !== ''
            ? DealType::from($dealTypeRaw)
            : null;

        return $this->json($this->mapStatsService->getStats($dealType));
    }
}
