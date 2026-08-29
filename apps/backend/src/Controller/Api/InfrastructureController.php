<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\InfrastructurePoiProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/infrastructure')]
class InfrastructureController extends AbstractController
{
    private const ALLOWED_TYPES = ['shop', 'pharmacy', 'school', 'park'];

    public function __construct(
        private readonly InfrastructurePoiProvider $poiProvider,
    ) {
    }

    #[Route('/pois', name: 'api_infrastructure_pois', methods: ['GET'])]
    public function pois(Request $request): JsonResponse
    {
        $typesRaw = $request->query->get('types', '');
        $types = array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) $typesRaw),
        ), static fn (string $value): bool => $value !== ''));

        $types = array_values(array_intersect($types, self::ALLOWED_TYPES));
        if ($types === []) {
            return $this->json(['message' => 'types_required'], Response::HTTP_BAD_REQUEST);
        }

        $south = $this->readFloat($request, 'south');
        $west = $this->readFloat($request, 'west');
        $north = $this->readFloat($request, 'north');
        $east = $this->readFloat($request, 'east');

        if ($south === null || $west === null || $north === null || $east === null) {
            return $this->json(['message' => 'bbox_required'], Response::HTTP_BAD_REQUEST);
        }

        if ($south >= $north || $west >= $east) {
            return $this->json(['message' => 'invalid_bbox'], Response::HTTP_BAD_REQUEST);
        }

        $zoom = (int) $request->query->get('zoom', 14);
        $zoom = max(8, min(18, $zoom));

        return $this->json([
            'items' => $this->poiProvider->getForViewport($types, $south, $west, $north, $east, $zoom),
        ]);
    }

    private function readFloat(Request $request, string $key): ?float
    {
        $value = $request->query->get($key);
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
