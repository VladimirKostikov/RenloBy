<?php

declare(strict_types=1);

namespace App\Service;

final class InfrastructurePoiProvider
{
    private const CACHE_TTL_SECONDS = 300;

    /** @var array<string, array{expiresAt: int, items: list<array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}>}> */
    private array $cache = [];

    public function __construct(
        private readonly YandexPlacesInfrastructureProvider $yandexPlacesProvider,
        private readonly GooglePlacesInfrastructureProvider $googlePlacesProvider,
        private readonly OverpassInfrastructureProvider $overpassProvider,
    ) {
    }

    /**
     * @param list<string> $types
     *
     * @return list<array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}>
     */
    public function getForViewport(
        array $types,
        float $south,
        float $west,
        float $north,
        float $east,
        int $zoom,
    ): array {
        $cacheKey = $this->cacheKey($types, $south, $west, $north, $east, $zoom);
        $cached = $this->cache[$cacheKey] ?? null;
        if ($cached !== null && $cached['expiresAt'] > time()) {
            return $cached['items'];
        }

        $items = $this->fetchProviders($types, $south, $west, $north, $east, $zoom);
        $this->cache[$cacheKey] = [
            'expiresAt' => time() + self::CACHE_TTL_SECONDS,
            'items' => $items,
        ];

        if (count($this->cache) > 64) {
            $this->pruneExpiredCache();
        }

        return $items;
    }

    /**
     * @param list<string> $types
     *
     * @return list<array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}>
     */
    private function fetchProviders(
        array $types,
        float $south,
        float $west,
        float $north,
        float $east,
        int $zoom,
    ): array {
        if ($this->yandexPlacesProvider->isConfigured()) {
            try {
                $items = $this->yandexPlacesProvider->search($types, $south, $west, $north, $east, $zoom);
                if ($items !== []) {
                    return $items;
                }
            } catch (\Throwable) {
            }
        }

        if ($this->googlePlacesProvider->isConfigured()) {
            try {
                $items = $this->googlePlacesProvider->search($types, $south, $west, $north, $east, $zoom);
                if ($items !== []) {
                    return $items;
                }
            } catch (\Throwable) {
            }
        }

        try {
            return $this->overpassProvider->search($types, $south, $west, $north, $east, $zoom);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<string> $types
     */
    private function cacheKey(
        array $types,
        float $south,
        float $west,
        float $north,
        float $east,
        int $zoom,
    ): string {
        $normalizedTypes = $types;
        sort($normalizedTypes);

        return md5(implode(',', [
            implode('|', $normalizedTypes),
            sprintf('%.4F', $south),
            sprintf('%.4F', $west),
            sprintf('%.4F', $north),
            sprintf('%.4F', $east),
            (string) $zoom,
        ]));
    }

    private function pruneExpiredCache(): void
    {
        $now = time();
        foreach ($this->cache as $key => $entry) {
            if ($entry['expiresAt'] <= $now) {
                unset($this->cache[$key]);
            }
        }

        if (count($this->cache) > 64) {
            $this->cache = array_slice($this->cache, -32, null, true);
        }
    }
}
