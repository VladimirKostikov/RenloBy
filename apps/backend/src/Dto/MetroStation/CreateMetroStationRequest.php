<?php

declare(strict_types=1);

namespace App\Dto\MetroStation;

readonly class CreateMetroStationRequest
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $lineColor,
        public int $cityId,
        public ?bool $isTest = null,
    ) {
    }
}
