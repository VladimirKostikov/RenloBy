<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Listing\AddressSuggestItemResponse;
use App\Repository\ListingRepository;

final class ListingAddressSuggestService
{
    private const KIND_STREET = 'street';
    private const KIND_DISTRICT = 'district';
    private const KIND_METRO = 'metro';
    private const KIND_CITY = 'city';

    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly ListingAddressNormalizer $addressNormalizer,
    ) {
    }

    /**
     * @return list<AddressSuggestItemResponse>
     */
    public function suggest(string $query, int $limit = 10): array
    {
        $needle = mb_strtolower(trim($query));
        if (mb_strlen($needle) < 2) {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $rows = $this->listingRepository->findAddressSuggestCandidates($needle, max(40, $limit * 8));

        $streets = [];
        $districts = [];
        $metros = [];
        $cities = [];

        foreach ($rows as $row) {
            $cityId = isset($row['cityId']) ? (int) $row['cityId'] : null;
            $cityName = trim((string) ($row['cityName'] ?? ''));
            $districtId = isset($row['districtId']) ? (int) $row['districtId'] : null;
            $districtName = trim((string) ($row['districtName'] ?? ''));
            $metroId = isset($row['metroStationId']) ? (int) $row['metroStationId'] : null;
            $metroName = trim((string) ($row['metroName'] ?? ''));
            $address = trim((string) ($row['address'] ?? ''));
            $street = $this->addressNormalizer->streetFromAddress($address);

            if ($street !== '' && $this->contains($street, $needle)) {
                $key = mb_strtolower($street) . '|' . ($cityId ?? 0);
                if (!isset($streets[$key])) {
                    $streets[$key] = new AddressSuggestItemResponse(
                        id: 'street-' . md5($key),
                        kind: self::KIND_STREET,
                        label: $street,
                        subtitle: $cityName !== '' ? $cityName : null,
                        query: $street,
                        cityId: $cityId,
                    );
                }
            }

            if ($districtId !== null && $districtName !== '' && $this->contains($districtName, $needle)) {
                $key = (string) $districtId;
                if (!isset($districts[$key])) {
                    $districts[$key] = new AddressSuggestItemResponse(
                        id: 'district-' . $districtId,
                        kind: self::KIND_DISTRICT,
                        label: $districtName,
                        subtitle: $cityName !== '' ? $cityName : null,
                        query: $districtName,
                        cityId: $cityId,
                        districtId: $districtId,
                    );
                }
            }

            if ($metroId !== null && $metroName !== '' && $this->contains($metroName, $needle)) {
                $key = (string) $metroId;
                if (!isset($metros[$key])) {
                    $metros[$key] = new AddressSuggestItemResponse(
                        id: 'metro-' . $metroId,
                        kind: self::KIND_METRO,
                        label: $metroName,
                        subtitle: $cityName !== '' ? $cityName : null,
                        query: $metroName,
                        cityId: $cityId,
                        metroStationId: $metroId,
                    );
                }
            }

            if ($cityId !== null && $cityName !== '' && $this->contains($cityName, $needle)) {
                $key = (string) $cityId;
                if (!isset($cities[$key])) {
                    $cities[$key] = new AddressSuggestItemResponse(
                        id: 'city-' . $cityId,
                        kind: self::KIND_CITY,
                        label: $cityName,
                        subtitle: null,
                        query: $cityName,
                        cityId: $cityId,
                    );
                }
            }
        }

        $merged = array_values(array_merge($streets, $districts, $metros, $cities));
        usort($merged, function (AddressSuggestItemResponse $a, AddressSuggestItemResponse $b) use ($needle): int {
            $rankA = $this->rank($a, $needle);
            $rankB = $this->rank($b, $needle);
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strcmp($a->label, $b->label);
        });

        return array_slice($merged, 0, $limit);
    }

    private function contains(string $haystack, string $needle): bool
    {
        return mb_strpos(mb_strtolower($haystack), $needle) !== false;
    }

    private function rank(AddressSuggestItemResponse $item, string $needle): int
    {
        $label = mb_strtolower($item->label);
        $kindWeight = match ($item->kind) {
            self::KIND_STREET => 0,
            self::KIND_DISTRICT => 1,
            self::KIND_METRO => 2,
            default => 3,
        };

        $prefixBonus = str_starts_with($label, $needle) ? 0 : 10;

        return $prefixBonus + $kindWeight;
    }
}
