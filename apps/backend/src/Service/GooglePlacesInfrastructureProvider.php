<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GooglePlacesInfrastructureProvider
{
    private const NEARBY_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';

    /** @var array<string, list<string>> */
    private const GOOGLE_TYPES_BY_INFRA = [
        'shop' => ['supermarket', 'grocery_or_supermarket', 'convenience_store'],
        'pharmacy' => ['pharmacy', 'drugstore'],
        'school' => ['school', 'primary_school', 'secondary_school'],
        'park' => ['park'],
    ];

    /** @var array<string, string> */
    private const INFRA_BY_GOOGLE_TYPE = [
        'supermarket' => 'shop',
        'grocery_or_supermarket' => 'shop',
        'convenience_store' => 'shop',
        'pharmacy' => 'pharmacy',
        'drugstore' => 'pharmacy',
        'school' => 'school',
        'primary_school' => 'school',
        'secondary_school' => 'school',
        'park' => 'park',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey = '',
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
    }

    /**
     * @param list<string> $types
     *
     * @return list<array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}>
     */
    public function search(
        array $types,
        float $south,
        float $west,
        float $north,
        float $east,
        int $zoom,
    ): array {
        if (!$this->isConfigured() || $types === []) {
            return [];
        }

        $seenPlaceIds = [];
        $pois = [];
        $centers = $this->buildSearchCenters($south, $west, $north, $east, $zoom);
        $pending = [];

        foreach ($types as $infraType) {
            $googleTypes = self::GOOGLE_TYPES_BY_INFRA[$infraType] ?? [];
            foreach ($googleTypes as $googleType) {
                foreach ($centers as $center) {
                    $pending[] = $this->startNearbyRequest(
                        $center['lat'],
                        $center['lng'],
                        $center['radius'],
                        $googleType,
                    );
                }
            }
        }

        foreach ($pending as $response) {
            foreach ($this->readNearbyPlaces($response) as $place) {
                $placeId = (string) ($place['place_id'] ?? '');
                if ($placeId === '' || isset($seenPlaceIds[$placeId])) {
                    continue;
                }

                $resolvedType = $this->resolveInfraType($place, $types);
                if ($resolvedType === null) {
                    continue;
                }

                $latitude = (float) ($place['geometry']['location']['lat'] ?? 0);
                $longitude = (float) ($place['geometry']['location']['lng'] ?? 0);
                if (!$this->inBbox($latitude, $longitude, $south, $west, $north, $east)) {
                    continue;
                }

                $seenPlaceIds[$placeId] = true;
                $pois[] = [
                    'id' => 'google-'.$placeId,
                    'type' => $resolvedType,
                    'name' => trim((string) ($place['name'] ?? '')) ?: $this->fallbackName($resolvedType),
                    'address' => $this->formatAddress($place, $latitude, $longitude),
                    'latitude' => round($latitude, 6),
                    'longitude' => round($longitude, 6),
                ];

                if (count($pois) >= $this->maxResults($zoom)) {
                    return $pois;
                }
            }
        }

        return $pois;
    }

    private function startNearbyRequest(float $lat, float $lng, int $radius, string $googleType): ResponseInterface
    {
        return $this->httpClient->request('GET', self::NEARBY_SEARCH_URL, [
            'query' => [
                'location' => sprintf('%.6F,%.6F', $lat, $lng),
                'radius' => max(200, min($radius, 50_000)),
                'type' => $googleType,
                'key' => $this->apiKey,
                'language' => 'ru',
            ],
            'timeout' => 8,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readNearbyPlaces(ResponseInterface $response): array
    {
        $payload = $response->toArray(false);
        $status = (string) ($payload['status'] ?? 'UNKNOWN');

        if ($status === 'ZERO_RESULTS') {
            return [];
        }

        if ($status !== 'OK') {
            throw new \RuntimeException(sprintf('google_places_%s', strtolower($status)));
        }

        $results = $payload['results'] ?? [];

        return is_array($results) ? $results : [];
    }

    /**
     * @return list<array{lat: float, lng: float, radius: int}>
     */
    private function buildSearchCenters(float $south, float $west, float $north, float $east, int $zoom): array
    {
        $centerLat = ($north + $south) / 2;
        $centerLng = ($east + $west) / 2;
        $cellRadiusM = match (true) {
            $zoom >= 16 => 700,
            $zoom >= 14 => 1200,
            $zoom >= 12 => 2200,
            default => 3500,
        };

        $latStep = $cellRadiusM * 2 / 111_320;
        $lngScale = max(cos(deg2rad($centerLat)), 0.2);
        $lngStep = $cellRadiusM * 2 / (111_320 * $lngScale);

        $centers = [];
        for ($lat = $south + $latStep / 2; $lat <= $north + 0.000001; $lat += $latStep) {
            for ($lng = $west + $lngStep / 2; $lng <= $east + 0.000001; $lng += $lngStep) {
                $centers[] = [
                    'lat' => $lat,
                    'lng' => $lng,
                    'radius' => $cellRadiusM,
                ];

                if (count($centers) >= $this->maxCenters($zoom)) {
                    return $centers;
                }
            }
        }

        if ($centers !== []) {
            return $centers;
        }

        return [[
            'lat' => $centerLat,
            'lng' => $centerLng,
            'radius' => min($cellRadiusM, $this->bboxRadiusMeters($centerLat, $south, $west, $north, $east)),
        ]];
    }

    /**
     * @param array<string, mixed> $place
     * @param list<string> $requestedTypes
     */
    private function resolveInfraType(array $place, array $requestedTypes): ?string
    {
        $googleTypes = $place['types'] ?? [];
        if (!is_array($googleTypes)) {
            return null;
        }

        foreach ($googleTypes as $googleType) {
            if (!is_string($googleType)) {
                continue;
            }

            $infraType = self::INFRA_BY_GOOGLE_TYPE[$googleType] ?? null;
            if ($infraType !== null && in_array($infraType, $requestedTypes, true)) {
                return $infraType;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $place
     */
    private function formatAddress(array $place, float $latitude, float $longitude): string
    {
        $vicinity = trim((string) ($place['vicinity'] ?? ''));
        if ($vicinity !== '') {
            return $vicinity;
        }

        $formatted = trim((string) ($place['formatted_address'] ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        return sprintf('%.5F, %.5F', $latitude, $longitude);
    }

    private function fallbackName(string $type): string
    {
        return match ($type) {
            'pharmacy' => 'Аптека',
            'school' => 'Школа',
            default => 'Магазин',
        };
    }

    private function inBbox(float $lat, float $lng, float $south, float $west, float $north, float $east): bool
    {
        return $lat >= $south && $lat <= $north && $lng >= $west && $lng <= $east;
    }

    private function bboxRadiusMeters(float $centerLat, float $south, float $west, float $north, float $east): int
    {
        $latSpan = ($north - $south) / 2;
        $lngSpan = ($east - $west) / 2;
        $latM = $latSpan * 111_320;
        $lngM = $lngSpan * 111_320 * max(cos(deg2rad($centerLat)), 0.2);

        return (int) min(50_000, max(300, hypot($latM, $lngM)));
    }

    private function maxCenters(int $zoom): int
    {
        return match (true) {
            $zoom >= 16 => 6,
            $zoom >= 14 => 4,
            default => 3,
        };
    }

    private function maxResults(int $zoom): int
    {
        return match (true) {
            $zoom >= 16 => 80,
            $zoom >= 14 => 60,
            default => 40,
        };
    }
}
