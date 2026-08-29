<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateSellerListingRequest
{
    /**
     * @param list<string> $images
     */
    public function __construct(
        #[Assert\NotNull(message: ApiErrorCode::VALIDATION_LISTING_DEAL_TYPE)]
        public DealType $dealType,
        #[Assert\NotNull(message: ApiErrorCode::VALIDATION_LISTING_TYPE)]
        public ListingType $listingType,
        #[Assert\Positive(message: ApiErrorCode::VALIDATION_LISTING_PRICE)]
        public int $price,
        #[Assert\Range(min: 0, max: 20, notInRangeMessage: ApiErrorCode::VALIDATION_LISTING_ROOMS)]
        public int $rooms,
        #[Assert\Positive(message: ApiErrorCode::VALIDATION_LISTING_AREA)]
        public float $area,
        #[Assert\Range(min: 0, max: 200, notInRangeMessage: ApiErrorCode::VALIDATION_LISTING_FLOOR)]
        public ?int $floor = null,
        #[Assert\Range(min: 1, max: 200, notInRangeMessage: ApiErrorCode::VALIDATION_LISTING_TOTAL_FLOORS)]
        public ?int $totalFloors = null,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_LISTING_ADDRESS)]
        #[Assert\Length(max: 255, maxMessage: ApiErrorCode::VALIDATION_LISTING_ADDRESS)]
        public string $address,
        #[Assert\NotNull(message: ApiErrorCode::VALIDATION_LISTING_COORDS)]
        public float $latitude,
        #[Assert\NotNull(message: ApiErrorCode::VALIDATION_LISTING_COORDS)]
        public float $longitude,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_LISTING_CITY)]
        #[Assert\Length(min: 1, max: 120, maxMessage: ApiErrorCode::VALIDATION_LISTING_CITY)]
        public string $city,
        #[Assert\Length(max: 120, maxMessage: ApiErrorCode::VALIDATION_LISTING_DISTRICT)]
        public ?string $district = null,
        #[Assert\Length(max: 120, maxMessage: ApiErrorCode::VALIDATION_LISTING_ADDRESS)]
        public ?string $metro = null,
        #[Assert\Regex(pattern: '/^#?[0-9A-Fa-f]{6}$/', message: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metroLineColor = null,
        public ?int $metroMinutes = null,
        public ?RentTerm $rentTerm = null,
        public bool $hasDeposit = false,
        public bool $utilitiesIncluded = false,
        public bool $noCommission = false,
        public bool $fromOwner = true,
        public bool $hasRenovation = false,
        public bool $priceNegotiable = false,
        #[Assert\Count(max: 10, maxMessage: ApiErrorCode::VALIDATION_LISTING_IMAGES)]
        public array $images = [],
        public ListingStatus $status = ListingStatus::Draft,
        #[Assert\Length(max: 255, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metaTitle = null,
        #[Assert\Length(max: 2000, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metaDescription = null,
        #[Assert\Length(max: 512, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metaKeywords = null,
    ) {
    }
}
