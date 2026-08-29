<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Enum\DealType;
use App\Enum\ListingType;

readonly class CreateListingRequest
{
    public function __construct(
        public DealType $dealType,
        public ListingType $listingType,
        public int $price,
        public int $rooms,
        public float $area,
        public ?int $floor = null,
        public ?int $totalFloors = null,
        public string $address,
        public float $latitude,
        public float $longitude,
        public string $city,
        public string $district,
        public int $userId,
        public ?string $metro = null,
        public ?int $metroMinutes = null,
        public bool $verified = false,
        public bool $aiGoodPrice = false,
        public array $images = [],
        public ?bool $isTest = null,
    ) {
    }
}
