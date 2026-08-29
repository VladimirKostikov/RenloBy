<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class YandexPlacesInfrastructureProvider
{
    private const SEARCH_URL = 'https://search-maps.yandex.ru/v1/';

    /** @var array<string, string> */
    private const QUERY_BY_TYPE = [
        'shop' => 'магазин',
        'pharmacy' => 'аптека',
        'school' => 'школа',
        'park' => 'парк',
    ];

    /** @var array<string, list<string>> */
    private const CATEGORY_CLASSES_BY_TYPE = [
        'shop' => ['supermarket', 'grocery', 'convenience', 'shopping_mall', 'mall', 'hypermarket', 'department_store', 'store', 'shop'],
        'pharmacy' => ['pharmacy', 'drugstore'],
        'school' => ['school', 'college', 'university', 'kindergarten', 'education'],
        'park' => ['park', 'garden', 'recreation'],
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

        $seen = [];
        $pois = [];
        $limit = $this->maxResults($zoom);
        $responses = [];

        foreach ($types as $infraType) {
            $query = self::QUERY_BY_TYPE[$infraType] ?? null;
            if ($query === null) {
                continue;
            }

            $responses[$infraType] = $this->startBizRequest($query, $south, $west, $north, $east, $limit);
        }

        foreach ($responses as $infraType => $response) {
            foreach ($this->readBizFeatures($response) as $feature) {
                $mapped = $this->mapFeature($feature, $infraType, $types, $south, $west, $north, $east);
                if ($mapped === null) {
                    continue;
                }

                if (isset($seen[$mapped['id']])) {
                    continue;
                }

                $seen[$mapped['id']] = true;
                $pois[] = $mapped;

                if (count($pois) >= $limit) {
                    return $pois;
                }
            }
        }

        return $pois;
    }

    private function startBizRequest(
        string $text,
        float $south,
        float $west,
        float $north,
        float $east,
        int $results,
    ): ResponseInterface {
        return $this->httpClient->request('GET', self::SEARCH_URL, [
            'query' => [
                'apikey' => $this->apiKey,
                'text' => $text,
                'type' => 'biz',
                'lang' => 'ru_RU',
                'results' => max(5, min($results, 50)),
                'rspn' => 1,
                'bbox' => sprintf('%.6F,%.6F~%.6F,%.6F', $west, $south, $east, $north),
            ],
            'timeout' => 8,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readBizFeatures(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw new \RuntimeException(sprintf('yandex_geosearch_http_%d', $status));
        }

        $payload = $response->toArray(false);
        $features = $payload['features'] ?? [];

        return is_array($features) ? $features : [];
    }

    /**
     * @param array<string, mixed> $feature
     * @param list<string> $requestedTypes
     *
     * @return array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}|null
     */
    private function mapFeature(
        array $feature,
        string $requestedType,
        array $requestedTypes,
        float $south,
        float $west,
        float $north,
        float $east,
    ): ?array {
        $geometry = $feature['geometry'] ?? null;
        $coordinates = is_array($geometry) ? ($geometry['coordinates'] ?? null) : null;
        if (!is_array($coordinates) || count($coordinates) < 2) {
            return null;
        }

        $longitude = (float) $coordinates[0];
        $latitude = (float) $coordinates[1];
        if (!$this->inBbox($latitude, $longitude, $south, $west, $north, $east)) {
            return null;
        }

        $properties = $feature['properties'] ?? [];
        if (!is_array($properties)) {
            return null;
        }

        $company = $properties['CompanyMetaData'] ?? [];
        if (!is_array($company)) {
            $company = [];
        }

        $resolvedType = $this->resolveType($company, $requestedType, $requestedTypes);
        if ($resolvedType === null) {
            return null;
        }

        $companyId = trim((string) ($company['id'] ?? ''));
        $id = $companyId !== ''
            ? 'yandex-'.$companyId
            : sprintf('yandex-%s-%.5F-%.5F', $resolvedType, $latitude, $longitude);

        $name = trim((string) ($company['name'] ?? $properties['name'] ?? ''));
        if ($name === '') {
            $name = $this->fallbackName($resolvedType);
        }

        $address = $this->formatAddress($company, $properties, $latitude, $longitude);

        return [
            'id' => $id,
            'type' => $resolvedType,
            'name' => $name,
            'address' => $address,
            'latitude' => round($latitude, 6),
            'longitude' => round($longitude, 6),
        ];
    }

    /**
     * @param array<string, mixed> $company
     * @param list<string> $requestedTypes
     */
    private function resolveType(array $company, string $requestedType, array $requestedTypes): ?string
    {
        $categories = $company['Categories'] ?? [];
        if (!is_array($categories) || $categories === []) {
            return in_array($requestedType, $requestedTypes, true) ? $requestedType : null;
        }

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            $class = strtolower(trim((string) ($category['class'] ?? '')));
            if ($class === '') {
                continue;
            }

            foreach (self::CATEGORY_CLASSES_BY_TYPE as $infraType => $classes) {
                if (!in_array($infraType, $requestedTypes, true)) {
                    continue;
                }

                foreach ($classes as $allowed) {
                    if ($class === $allowed || str_contains($class, $allowed)) {
                        return $infraType;
                    }
                }
            }
        }

        return in_array($requestedType, $requestedTypes, true) ? $requestedType : null;
    }

    /**
     * @param array<string, mixed> $company
     * @param array<string, mixed> $properties
     */
    private function formatAddress(array $company, array $properties, float $latitude, float $longitude): string
    {
        $address = trim((string) ($company['address'] ?? ''));
        if ($address !== '') {
            return $address;
        }

        $formatted = $company['Address']['formatted'] ?? null;
        if (is_string($formatted) && trim($formatted) !== '') {
            return trim($formatted);
        }

        $description = trim((string) ($properties['description'] ?? ''));
        if ($description !== '') {
            return $description;
        }

        return sprintf('%.5F, %.5F', $latitude, $longitude);
    }

    private function fallbackName(string $type): string
    {
        return match ($type) {
            'pharmacy' => 'Аптека',
            'school' => 'Школа',
            'park' => 'Парк',
            default => 'Магазин',
        };
    }

    private function inBbox(float $lat, float $lng, float $south, float $west, float $north, float $east): bool
    {
        return $lat >= $south && $lat <= $north && $lng >= $west && $lng <= $east;
    }

    private function maxResults(int $zoom): int
    {
        return match (true) {
            $zoom >= 16 => 40,
            $zoom >= 14 => 30,
            default => 20,
        };
    }
}
