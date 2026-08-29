<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;
use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateSellerListingRequest
{
    /**
     * @param list<string>|null $images
     */
    public function __construct(
        public ?DealType $dealType = null,
        public ?ListingType $listingType = null,
        #[Assert\Positive(message: ApiErrorCode::VALIDATION_LISTING_PRICE)]
        public ?int $price = null,
        #[Assert\Range(min: 0, max: 20, notInRangeMessage: ApiErrorCode::VALIDATION_LISTING_ROOMS)]
        public ?int $rooms = null,
        #[Assert\Positive(message: ApiErrorCode::VALIDATION_LISTING_AREA)]
        public ?float $area = null,
        #[Assert\Range(min: 0, max: 200, notInRangeMessage: ApiErrorCode::VALIDATION_LISTING_FLOOR)]
        public ?int $floor = null,
        public bool $clearFloor = false,
        #[Assert\Range(min: 1, max: 200, notInRangeMessage: ApiErrorCode::VALIDATION_LISTING_TOTAL_FLOORS)]
        public ?int $totalFloors = null,
        public bool $clearTotalFloors = false,
        #[Assert\Length(min: 1, max: 255, maxMessage: ApiErrorCode::VALIDATION_LISTING_ADDRESS)]
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        #[Assert\Length(min: 1, max: 120, maxMessage: ApiErrorCode::VALIDATION_LISTING_CITY)]
        public ?string $city = null,
        #[Assert\Length(min: 1, max: 120, maxMessage: ApiErrorCode::VALIDATION_LISTING_DISTRICT)]
        public ?string $district = null,
        #[Assert\Length(max: 120)]
        public ?string $metro = null,
        public bool $clearMetro = false,
        #[Assert\Regex(pattern: '/^#?[0-9A-Fa-f]{6}$/', message: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metroLineColor = null,
        public ?int $metroMinutes = null,
        public bool $clearMetroMinutes = false,
        public ?RentTerm $rentTerm = null,
        public ?bool $hasDeposit = null,
        public ?bool $utilitiesIncluded = null,
        public ?bool $noCommission = null,
        public ?bool $fromOwner = null,
        public ?bool $hasRenovation = null,
        public ?bool $priceNegotiable = null,
        #[Assert\Count(max: 10, maxMessage: ApiErrorCode::VALIDATION_LISTING_IMAGES)]
        public ?array $images = null,
        public ?ListingStatus $status = null,
        #[Assert\Length(max: 255, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metaTitle = null,
        public bool $metaTitleProvided = false,
        #[Assert\Length(max: 2000, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metaDescription = null,
        public bool $metaDescriptionProvided = false,
        #[Assert\Length(max: 512, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public ?string $metaKeywords = null,
        public bool $metaKeywordsProvided = false,
    ) {
    }
}
