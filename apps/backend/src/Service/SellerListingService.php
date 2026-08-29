<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Listing\CreateSellerListingRequest;
use App\Dto\Listing\ListingResponse;
use App\Dto\Listing\UpdateSellerListingRequest;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\ListingStatus;
use App\Exception\ForbiddenException;
use App\Http\ApiErrorCode;
use Doctrine\ORM\EntityManagerInterface;

class SellerListingService
{
    public function __construct(
        private readonly ListingService $listingService,
        private readonly LocationTextResolver $locationTextResolver,
        private readonly EntityManagerInterface $entityManager,
        private readonly SellerProfileGate $sellerProfileGate,
        private readonly ListingGoodPriceEvaluator $listingGoodPriceEvaluator,
    ) {
    }

    public function create(User $user, CreateSellerListingRequest $request): ListingResponse
    {
        $this->sellerProfileGate->assertComplete($user);
        $status = $this->normalizeSellerStatus($request->status);

        $city = $this->locationTextResolver->resolveCity($request->city);
        $districtName = trim((string) ($request->district ?? ''));
        $district = $districtName !== ''
            ? $this->locationTextResolver->resolveDistrict($city, $districtName)
            : null;
        $metro = $this->locationTextResolver->resolveMetroStation(
            $city,
            $request->metro,
            true,
            $request->metroLineColor,
        );

        $listing = (new Listing())
            ->setDealType($request->dealType)
            ->setListingType($request->listingType)
            ->setStatus($status)
            ->setPrice($request->price)
            ->setPricePerSqm($this->pricePerSqm($request->price, $request->area))
            ->setRooms($request->rooms)
            ->setArea($request->area)
            ->setFloor($request->floor)
            ->setTotalFloors($request->totalFloors)
            ->setAddress(trim($request->address))
            ->setLatitude($request->latitude)
            ->setLongitude($request->longitude)
            ->setMetroMinutes($request->metroMinutes)
            ->setRentTerm($request->rentTerm)
            ->setHasDeposit($request->hasDeposit)
            ->setUtilitiesIncluded($request->utilitiesIncluded)
            ->setNoCommission($request->noCommission)
            ->setFromOwner($request->fromOwner)
            ->setHasRenovation($request->hasRenovation)
            ->setPriceNegotiable($request->priceNegotiable)
            ->setImages($this->sanitizeImages($request->images))
            ->setMetaTitle($this->normalizeNullableText($request->metaTitle))
            ->setMetaDescription($this->normalizeNullableText($request->metaDescription))
            ->setMetaKeywords($this->normalizeNullableText($request->metaKeywords))
            ->setUser($user)
            ->setCity($city)
            ->setDistrict($district)
            ->setMetroStation($metro);

        $this->listingGoodPriceEvaluator->apply($listing);
        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        return ListingResponse::fromEntity($listing);
    }

    public function update(User $user, int $id, UpdateSellerListingRequest $request): ListingResponse
    {
        $listing = $this->ownedListing($user, $id);

        if ($request->dealType !== null) {
            $listing->setDealType($request->dealType);
        }
        if ($request->listingType !== null) {
            $listing->setListingType($request->listingType);
        }
        if ($request->price !== null) {
            $listing->setPrice($request->price);
        }
        if ($request->rooms !== null) {
            $listing->setRooms($request->rooms);
        }
        if ($request->area !== null) {
            $listing->setArea($request->area);
        }
        if ($request->clearFloor) {
            $listing->setFloor(null);
        } elseif ($request->floor !== null) {
            $listing->setFloor($request->floor);
        }
        if ($request->clearTotalFloors) {
            $listing->setTotalFloors(null);
        } elseif ($request->totalFloors !== null) {
            $listing->setTotalFloors($request->totalFloors);
        }
        if ($request->address !== null) {
            $listing->setAddress(trim($request->address));
        }
        if ($request->latitude !== null) {
            $listing->setLatitude($request->latitude);
        }
        if ($request->longitude !== null) {
            $listing->setLongitude($request->longitude);
        }
        if ($request->clearMetroMinutes) {
            $listing->setMetroMinutes(null);
        } elseif ($request->metroMinutes !== null) {
            $listing->setMetroMinutes($request->metroMinutes);
        }
        if ($request->rentTerm !== null) {
            $listing->setRentTerm($request->rentTerm);
        }
        if ($request->hasDeposit !== null) {
            $listing->setHasDeposit($request->hasDeposit);
        }
        if ($request->utilitiesIncluded !== null) {
            $listing->setUtilitiesIncluded($request->utilitiesIncluded);
        }
        if ($request->noCommission !== null) {
            $listing->setNoCommission($request->noCommission);
        }
        if ($request->fromOwner !== null) {
            $listing->setFromOwner($request->fromOwner);
        }
        if ($request->hasRenovation !== null) {
            $listing->setHasRenovation($request->hasRenovation);
        }
        if ($request->priceNegotiable !== null) {
            $listing->setPriceNegotiable($request->priceNegotiable);
        }
        if ($request->images !== null) {
            $listing->setImages($this->sanitizeImages($request->images));
        }
        if ($request->metaTitleProvided) {
            $listing->setMetaTitle($this->normalizeNullableText($request->metaTitle));
        }
        if ($request->metaDescriptionProvided) {
            $listing->setMetaDescription($this->normalizeNullableText($request->metaDescription));
        }
        if ($request->metaKeywordsProvided) {
            $listing->setMetaKeywords($this->normalizeNullableText($request->metaKeywords));
        }

        $city = $listing->getCity();
        if ($request->city !== null) {
            $city = $this->locationTextResolver->resolveCity($request->city);
            $listing->setCity($city);
        }
        if ($request->district !== null) {
            $city ??= $listing->getCity();
            $districtName = trim($request->district);
            if ($districtName === '') {
                $listing->setDistrict(null);
            } elseif ($city !== null) {
                $listing->setDistrict($this->locationTextResolver->resolveDistrict($city, $districtName));
            }
        }
        if ($request->clearMetro) {
            $listing->setMetroStation(null);
        } elseif ($request->metro !== null) {
            $city ??= $listing->getCity();
            if ($city !== null) {
                $listing->setMetroStation($this->locationTextResolver->resolveMetroStation(
                    $city,
                    $request->metro,
                    true,
                    $request->metroLineColor,
                ));
            }
        }

        if ($request->status !== null) {
            $this->applyStatus($listing, $request->status);
        }

        $listing->setPricePerSqm($this->pricePerSqm($listing->getPrice(), $listing->getArea()));
        $this->listingGoodPriceEvaluator->apply($listing);
        $this->entityManager->flush();

        return ListingResponse::fromEntity($listing);
    }

    public function publish(User $user, int $id): ListingResponse
    {
        $this->sellerProfileGate->assertComplete($user);
        $listing = $this->ownedListing($user, $id);
        $this->applyStatus($listing, ListingStatus::Pending);
        $this->listingGoodPriceEvaluator->apply($listing);
        $this->entityManager->flush();

        return ListingResponse::fromEntity($listing);
    }

    public function archive(User $user, int $id): ListingResponse
    {
        $listing = $this->ownedListing($user, $id);
        $this->applyStatus($listing, ListingStatus::Archived);
        $this->entityManager->flush();

        return ListingResponse::fromEntity($listing);
    }

    public function deleteDraft(User $user, int $id): void
    {
        $this->deleteRemovable($user, $id);
    }

    public function deleteRemovable(User $user, int $id): void
    {
        $listing = $this->ownedListing($user, $id);
        $status = $listing->getStatus();
        if ($status !== ListingStatus::Draft && $status !== ListingStatus::Pending) {
            throw new ForbiddenException(ApiErrorCode::FORBIDDEN_LISTING);
        }
        $listing->softDelete();
        $this->entityManager->flush();
    }

    private function ownedListing(User $user, int $id): Listing
    {
        $listing = $this->listingService->findEntity($id);
        if ($listing->getUser()?->getId() !== $user->getId()) {
            throw new ForbiddenException(ApiErrorCode::FORBIDDEN_LISTING);
        }

        return $listing;
    }

    private function applyStatus(Listing $listing, ListingStatus $status): void
    {
        $listing->setStatus($this->normalizeSellerStatus($status));
    }

    private function normalizeSellerStatus(ListingStatus $status): ListingStatus
    {
        return match ($status) {
            ListingStatus::Published, ListingStatus::Pending, ListingStatus::Rejected => ListingStatus::Pending,
            ListingStatus::Archived => ListingStatus::Archived,
            default => ListingStatus::Draft,
        };
    }

    /**
     * @param list<mixed> $images
     * @return list<string>
     */
    private function sanitizeImages(array $images): array
    {
        $result = [];
        foreach ($images as $image) {
            if (!is_string($image)) {
                continue;
            }
            $url = trim($image);
            if ($url === '' || !$this->isAllowedImageUrl($url)) {
                continue;
            }
            $result[] = $url;
            if (count($result) >= 10) {
                break;
            }
        }

        return $result;
    }

    private function isAllowedImageUrl(string $url): bool
    {
        if (str_starts_with($url, '/uploads/')) {
            return !str_contains($url, '..') && preg_match('#^/uploads/[a-z0-9/._-]+$#i', $url) === 1;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return preg_match('#^https?://#i', $url) === 1;
    }

    private function pricePerSqm(int $price, float $area): int
    {
        if ($area <= 0) {
            return 0;
        }

        return (int) round($price / $area);
    }

    private function normalizeNullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
