<?php

declare(strict_types=1);

namespace App\Dto\Map;

readonly class MapZoneStatsItem
{
    public function __construct(
        public int $id,
        public int $count,
        public int $avgPrice,
        public int $avgPricePerSqm,
    ) {
    }
}
