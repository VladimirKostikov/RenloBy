<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;

readonly class UpdateListingRequest
{
    public function __construct(
        public ?DealType $dealType = null,
        public ?ListingType $listingType = null,
        public ?int $price = null,
        public ?int $rooms = null,
        public ?float $area = null,
        public ?int $floor = null,
        public bool $clearFloor = false,
        public ?int $totalFloors = null,
        public bool $clearTotalFloors = false,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $metro = null,
        public bool $clearMetro = false,
        public ?int $metroMinutes = null,
        public bool $clearMetroMinutes = false,
        public ?bool $verified = null,
        public ?bool $aiGoodPrice = null,
        public ?array $images = null,
        public ?bool $isTest = null,
        public ?ListingStatus $status = null,
    ) {
    }
}
