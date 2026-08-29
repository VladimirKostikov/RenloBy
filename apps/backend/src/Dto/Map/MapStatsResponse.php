<?php

declare(strict_types=1);

namespace App\Dto\Map;

readonly class MapStatsResponse
{
    public function __construct(
        public array $cities,
        public array $districts,
    ) {
    }
}
