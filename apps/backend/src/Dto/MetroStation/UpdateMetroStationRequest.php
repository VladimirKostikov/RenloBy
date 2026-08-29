<?php

declare(strict_types=1);

namespace App\Dto\MetroStation;

readonly class UpdateMetroStationRequest
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $lineColor = null,
        public ?int $cityId = null,
        public ?bool $isTest = null,
    ) {
    }
}
