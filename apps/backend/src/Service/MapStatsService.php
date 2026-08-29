<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Map\MapStatsResponse;
use App\Dto\Map\MapZoneStatsItem;
use App\Enum\DealType;
use App\Repository\ListingRepository;

class MapStatsService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {
    }

    public function getStats(?DealType $dealType): MapStatsResponse
    {
        return new MapStatsResponse(
            $this->mapRows($this->listingRepository->statsByCity($dealType)),
            $this->mapRows($this->listingRepository->statsByDistrict($dealType)),
        );
    }

    private function mapRows(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $result[] = new MapZoneStatsItem(
                $id,
                (int) $row['count'],
                (int) round((float) $row['avgPrice']),
                (int) round((float) $row['avgPricePerSqm']),
            );
        }

        return $result;
    }
}
