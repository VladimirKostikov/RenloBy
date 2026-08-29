<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OverpassInfrastructureProvider
{
    /** @var list<string> */
    private const OVERPASS_URLS = [
        'https://overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
    ];

    /** @var array<string, list<string>> */
    private const TYPE_FILTERS = [
        'shop' => [
            'node["shop"]({bbox});',
            'way["shop"]({bbox});',
        ],
        'pharmacy' => [
            'node["amenity"="pharmacy"]({bbox});',
            'way["amenity"="pharmacy"]({bbox});',
        ],
        'school' => [
            'node["amenity"~"^(school|kindergarten)$"]({bbox});',
            'way["amenity"~"^(school|kindergarten)$"]({bbox});',
        ],
        'park' => [
            'node["leisure"~"^(park|garden)$"]({bbox});',
            'way["leisure"~"^(park|garden)$"]({bbox});',
        ],
    ];

    /** @var array<string, string> */
    private const FALLBACK_NAMES = [
        'shop' => 'Магазин',
        'pharmacy' => 'Аптека',
        'school' => 'Школа',
        'park' => 'Парк',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
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
        if ($types === []) {
            return [];
        }

        $query = $this->buildQuery($types, $south, $west, $north, $east, $zoom);
        if ($query === '') {
            return [];
        }

        $payload = $this->requestOverpass($query);
        $elements = is_array($payload['elements'] ?? null) ? $payload['elements'] : [];

        return $this->mapElements($elements, $types, $zoom);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestOverpass(string $query): array
    {
        $lastException = null;

        foreach (self::OVERPASS_URLS as $url) {
            try {
                $response = $this->httpClient->request('POST', $url, [
                    'body' => ['data' => $query],
                    'timeout' => 10,
                ]);

                $payload = $response->toArray(false);
                if (is_array($payload['elements'] ?? null)) {
                    return $payload;
                }
            } catch (\Throwable $exception) {
                $lastException = $exception;
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        return [];
    }

    /**
     * @param list<string> $types
     */
    private function buildQuery(array $types, float $south, float $west, float $north, float $east, int $zoom): string
    {
        $bbox = sprintf('%F,%F,%F,%F', $south, $west, $north, $east);
        $filters = [];

        foreach ($types as $type) {
            foreach (self::TYPE_FILTERS[$type] ?? [] as $filter) {
                $filters[] = str_replace('{bbox}', $bbox, $filter);
            }
        }

        if ($filters === []) {
            return '';
        }

        $limit = match (true) {
            $zoom >= 16 => 120,
            $zoom >= 14 => 80,
            $zoom >= 12 => 50,
            default => 30,
        };

        return '[out:json][timeout:10];(' . implode('', $filters) . ');out center ' . $limit . ';';
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param list<string>              $types
     *
     * @return list<array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}>
     */
    private function mapElements(array $elements, array $types, int $zoom): array
    {
        $allowed = array_fill_keys($types, true);
        $seen = [];
        $items = [];

        foreach ($elements as $element) {
            if (!is_array($element)) {
                continue;
            }

            $infraType = $this->resolveType($element);
            if ($infraType === null || !isset($allowed[$infraType])) {
                continue;
            }

            $latitude = $this->readCoordinate($element, 'lat', ['center', 'lat']);
            $longitude = $this->readCoordinate($element, 'lon', ['center', 'lon']);
            if ($latitude === null || $longitude === null) {
                continue;
            }

            $elementId = (string) ($element['id'] ?? '');
            $elementType = (string) ($element['type'] ?? 'node');
            if ($elementId === '') {
                continue;
            }

            $id = $infraType . '-' . $elementType . '-' . $elementId;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
            $name = trim((string) ($tags['name'] ?? $tags['name:ru'] ?? ''));
            if ($name === '') {
                $name = self::FALLBACK_NAMES[$infraType];
            }

            $items[] = [
                'id' => $id,
                'type' => $infraType,
                'name' => $name,
                'address' => $this->buildAddress($tags, $latitude, $longitude),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        }

        $maxItems = match (true) {
            $zoom >= 16 => 100,
            $zoom >= 14 => 70,
            $zoom >= 12 => 45,
            default => 25,
        };

        if (count($items) <= $maxItems) {
            return $items;
        }

        return array_slice($items, 0, $maxItems);
    }

    /**
     * @param array<string, mixed> $element
     */
    private function resolveType(array $element): ?string
    {
        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];

        if (($tags['amenity'] ?? null) === 'pharmacy') {
            return 'pharmacy';
        }

        $amenity = (string) ($tags['amenity'] ?? '');
        if ($amenity === 'school' || $amenity === 'kindergarten') {
            return 'school';
        }

        $leisure = (string) ($tags['leisure'] ?? '');
        if ($leisure === 'park' || $leisure === 'garden') {
            return 'park';
        }

        if (isset($tags['shop']) && (string) $tags['shop'] !== '') {
            return 'shop';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $element
     * @param list<string>         $nestedPath
     */
    private function readCoordinate(array $element, string $directKey, array $nestedPath): ?float
    {
        if (isset($element[$directKey]) && is_numeric($element[$directKey])) {
            return (float) $element[$directKey];
        }

        $nested = $element[$nestedPath[0]] ?? null;
        if (is_array($nested) && isset($nested[$nestedPath[1]]) && is_numeric($nested[$nestedPath[1]])) {
            return (float) $nested[$nestedPath[1]];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $tags
     */
    private function buildAddress(array $tags, float $latitude, float $longitude): string
    {
        $street = trim((string) ($tags['addr:street'] ?? ''));
        $house = trim((string) ($tags['addr:housenumber'] ?? ''));

        if ($street !== '') {
            return $house !== '' ? $street . ', ' . $house : $street;
        }

        return sprintf('%F, %F', $latitude, $longitude);
    }
}
