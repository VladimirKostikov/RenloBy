<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;

readonly class ListingSearchRequest
{
    public function __construct(
        public ?DealType $dealType = null,
        public ?ListingType $listingType = null,
        public ?ListingStatus $status = null,
        public ?int $cityId = null,
        public ?string $regionSlug = null,
        public ?int $districtId = null,
        public ?int $rooms = null,
        public ?int $floor = null,
        public ?float $minArea = null,
        public ?float $maxArea = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?bool $verified = null,
        public ?RentTerm $rentTerm = null,
        public ?bool $hasDeposit = null,
        public ?bool $utilitiesIncluded = null,
        public ?bool $noCommission = null,
        public ?bool $fromOwner = null,
        public ?bool $hasRenovation = null,
        public ?string $query = null,
        public ?int $userId = null,
        public bool $includeNonPublished = false,
        public string $sort = 'publishedAt',
        public string $direction = 'DESC',
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
