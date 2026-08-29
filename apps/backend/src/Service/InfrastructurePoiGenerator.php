<?php

declare(strict_types=1);

namespace App\Service;

final class InfrastructurePoiGenerator
{
    /** @var array<string, list<string>> */
    private const TYPE_NAMES = [
        'shop' => ['Продукты', 'Супермаркет', 'Евроопт', 'Корона', 'Соседи', 'Green', 'Магазин у дома'],
        'pharmacy' => ['Аптека', 'Белфармация', 'Аптека 24', 'Фитофarm', 'Аптека здоровья'],
        'school' => ['Школа', 'Гимназия', 'Лицей', 'Садик «Солнышко»', 'СШ №1'],
    ];

    /**
     * @param list<string> $types
     * @param list<array{id: int|string, latitude: float|string, longitude: float|string, address?: string|null}> $listings
     *
     * @return list<array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}>
     */
    public function generate(
        array $types,
        array $listings,
        float $south,
        float $west,
        float $north,
        float $east,
        int $zoom,
    ): array {
        unset($zoom);

        if ($types === []) {
            return [];
        }

        $seen = [];
        $pois = [];

        foreach ($listings as $listing) {
            $listingId = (int) $listing['id'];
            $lat = round((float) $listing['latitude'], 6);
            $lng = round((float) $listing['longitude'], 6);
            $address = trim((string) ($listing['address'] ?? ''));

            if ($address === '') {
                continue;
            }

            if (!$this->inBbox($lat, $lng, $south, $west, $north, $east)) {
                continue;
            }

            foreach ($types as $type) {
                $key = sprintf('%s:%d', $type, $listingId);
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $pois[] = $this->makePoi(
                    sprintf('listing-%d-%s', $listingId, $type),
                    $type,
                    $this->nameFor($type, $listingId),
                    $address,
                    $lat,
                    $lng,
                );
            }
        }

        return $pois;
    }

    private function inBbox(float $lat, float $lng, float $south, float $west, float $north, float $east): bool
    {
        return $lat >= $south && $lat <= $north && $lng >= $west && $lng <= $east;
    }

    private function nameFor(string $type, int $seed): string
    {
        $names = self::TYPE_NAMES[$type] ?? ['Объект'];

        return $names[abs($seed) % count($names)];
    }

    /**
     * @return array{id: string, type: string, name: string, address: string, latitude: float, longitude: float}
     */
    private function makePoi(string $id, string $type, string $name, string $address, float $lat, float $lng): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'address' => $address,
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }
}
