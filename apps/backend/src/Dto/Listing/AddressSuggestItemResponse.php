<?php

declare(strict_types=1);

namespace App\Dto\Listing;

final class AddressSuggestItemResponse
{
    public function __construct(
        public string $id,
        public string $kind,
        public string $label,
        public ?string $subtitle,
        public string $query,
        public ?int $cityId = null,
        public ?int $districtId = null,
        public ?int $metroStationId = null,
    ) {
    }
}
