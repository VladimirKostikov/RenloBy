<?php

declare(strict_types=1);

namespace App\Dto\Listing;

use App\Entity\Listing;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Enum\RentTerm;

readonly class ListingResponse
{
    public function __construct(
        public int $id,
        public DealType $dealType,
        public ListingType $listingType,
        public ListingStatus $status,
        public int $price,
        public int $pricePerSqm,
        public int $rooms,
        public float $area,
        public ?int $floor,
        public ?int $totalFloors,
        public string $address,
        public float $latitude,
        public float $longitude,
        public ?int $metroMinutes,
        public bool $verified,
        public bool $aiGoodPrice,
        public ?RentTerm $rentTerm,
        public bool $hasDeposit,
        public bool $utilitiesIncluded,
        public bool $noCommission,
        public bool $fromOwner,
        public bool $hasRenovation,
        public bool $priceNegotiable,
        public int $views,
        public array $images,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public ?string $metaKeywords,
        public string $publishedAt,
        public int $userId,
        public int $cityId,
        public ?int $districtId,
        public ?int $metroStationId,
        public string $cityName,
        public string $districtName,
        public ?string $metroStationName,
        public bool $isTest,
        public ?ListingSellerResponse $seller = null,
    ) {
    }

    public static function fromEntity(Listing $listing): self
    {
        $user = $listing->getUser();

        return new self(
            $listing->getId() ?? 0,
            $listing->getDealType(),
            $listing->getListingType(),
            $listing->getStatus(),
            $listing->getPrice(),
            $listing->getPricePerSqm(),
            $listing->getRooms(),
            $listing->getArea(),
            $listing->getFloor(),
            $listing->getTotalFloors(),
            $listing->getAddress(),
            $listing->getLatitude(),
            $listing->getLongitude(),
            $listing->getMetroMinutes(),
            $listing->isVerified(),
            $listing->isAiGoodPrice(),
            $listing->getRentTerm(),
            $listing->hasDeposit(),
            $listing->isUtilitiesIncluded(),
            $listing->isNoCommission(),
            $listing->isFromOwner(),
            $listing->hasRenovation(),
            $listing->isPriceNegotiable(),
            $listing->getViews(),
            $listing->getImages(),
            $listing->getMetaTitle(),
            $listing->getMetaDescription(),
            $listing->getMetaKeywords(),
            $listing->getPublishedAt()->format(\DateTimeInterface::ATOM),
            $user?->getId() ?? 0,
            $listing->getCity()?->getId() ?? 0,
            $listing->getDistrict()?->getId(),
            $listing->getMetroStation()?->getId(),
            $listing->getCity()?->getName() ?? '',
            $listing->getDistrict()?->getName() ?? '',
            $listing->getMetroStation()?->getName(),
            $listing->isTest(),
            $user instanceof \App\Entity\User ? ListingSellerResponse::fromEntity($user) : null,
        );
    }
}
