<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Common\PaginatedResponse;
use App\Dto\Listing\CreateListingRequest;
use App\Dto\Listing\ListingResponse;
use App\Dto\Listing\ListingSearchRequest;
use App\Dto\Listing\UpdateListingRequest;
use App\Entity\Listing;
use App\Entity\User;
use App\Http\ApiErrorCode;
use App\Exception\ResourceNotFoundException;
use App\Message\ListingCreatedMessage;
use App\Repository\ListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ListingService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
        private readonly LocationTextResolver $locationTextResolver,
        private readonly AuthService $authService,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly UserNotificationService $userNotificationService,
        private readonly ListingAnalyticsRecorder $listingAnalyticsRecorder,
        private readonly ListingGoodPriceEvaluator $listingGoodPriceEvaluator,
    ) {
    }

    public function search(ListingSearchRequest $request): PaginatedResponse
    {
        $result = $this->listingRepository->search($request);

        return new PaginatedResponse(
            array_map(fn (Listing $listing) => ListingResponse::fromEntity($listing), $result['items']),
            $result['total'],
            $result['page'],
            $result['limit'],
        );
    }

    public function searchAdmin(ListingSearchRequest $request): PaginatedResponse
    {
        return $this->search(new ListingSearchRequest(
            dealType: $request->dealType,
            listingType: $request->listingType,
            status: $request->status,
            cityId: $request->cityId,
            regionSlug: $request->regionSlug,
            districtId: $request->districtId,
            rooms: $request->rooms,
            floor: $request->floor,
            minArea: $request->minArea,
            maxArea: $request->maxArea,
            minPrice: $request->minPrice,
            maxPrice: $request->maxPrice,
            verified: $request->verified,
            rentTerm: $request->rentTerm,
            hasDeposit: $request->hasDeposit,
            utilitiesIncluded: $request->utilitiesIncluded,
            noCommission: $request->noCommission,
            fromOwner: $request->fromOwner,
            hasRenovation: $request->hasRenovation,
            query: $request->query,
            userId: $request->userId,
            includeNonPublished: true,
            sort: $request->sort,
            direction: $request->direction,
            page: $request->page,
            limit: $request->limit,
        ));
    }

    public function searchForUser(User $user, ListingSearchRequest $request): PaginatedResponse
    {
        $scoped = new ListingSearchRequest(
            dealType: $request->dealType,
            listingType: $request->listingType,
            status: $request->status,
            cityId: $request->cityId,
            regionSlug: $request->regionSlug,
            districtId: $request->districtId,
            rooms: $request->rooms,
            floor: $request->floor,
            minArea: $request->minArea,
            maxArea: $request->maxArea,
            minPrice: $request->minPrice,
            maxPrice: $request->maxPrice,
            verified: $request->verified,
            rentTerm: $request->rentTerm,
            hasDeposit: $request->hasDeposit,
            utilitiesIncluded: $request->utilitiesIncluded,
            noCommission: $request->noCommission,
            fromOwner: $request->fromOwner,
            hasRenovation: $request->hasRenovation,
            query: $request->query,
            userId: $user->getId(),
            includeNonPublished: true,
            sort: $request->sort,
            direction: $request->direction,
            page: $request->page,
            limit: $request->limit,
        );

        return $this->search($scoped);
    }

    public function get(int $id): ListingResponse
    {
        $listing = $this->findEntity($id);
        if ($listing->getStatus() !== \App\Enum\ListingStatus::Published) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
        }
        $this->listingAnalyticsRecorder->recordView($listing);

        return ListingResponse::fromEntity($listing);
    }

    public function getAdmin(int $id): ListingResponse
    {
        return ListingResponse::fromEntity($this->findEntity($id));
    }

    public function create(CreateListingRequest $request): ListingResponse
    {
        $isTest = $request->isTest ?? true;
        $city = $this->locationTextResolver->resolveCity($request->city, $isTest);
        $districtName = trim($request->district);
        $district = $districtName !== ''
            ? $this->locationTextResolver->resolveDistrict($city, $districtName, $isTest)
            : null;
        $metro = $this->locationTextResolver->resolveMetroStation($city, $request->metro, $isTest);

        $listing = (new Listing())
            ->setDealType($request->dealType)
            ->setListingType($request->listingType)
            ->setStatus(\App\Enum\ListingStatus::Published)
            ->setPrice($request->price)
            ->setPricePerSqm($this->calculatePricePerSqm($request->price, $request->area))
            ->setRooms($request->rooms)
            ->setArea($request->area)
            ->setFloor($request->floor)
            ->setTotalFloors($request->totalFloors)
            ->setAddress($request->address)
            ->setLatitude($request->latitude)
            ->setLongitude($request->longitude)
            ->setMetroMinutes($request->metroMinutes)
            ->setVerified($request->verified)
            ->setImages($this->sanitizeImages($request->images))
            ->setUser($this->authService->getUserById($request->userId))
            ->setCity($city)
            ->setDistrict($district)
            ->setMetroStation($metro)
            ->setIsTest($isTest);

        $this->listingGoodPriceEvaluator->apply($listing);

        $this->entityManager->persist($listing);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new ListingCreatedMessage($listing->getId() ?? 0));

        return ListingResponse::fromEntity($listing);
    }

    public function update(int $id, UpdateListingRequest $request): ListingResponse
    {
        $listing = $this->findEntity($id);

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
            $listing->setAddress($request->address);
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
        if ($request->verified !== null) {
            $listing->setVerified($request->verified);
        }
        if ($request->images !== null) {
            $listing->setImages($this->sanitizeImages($request->images));
        }
        if ($request->isTest !== null) {
            $listing->setIsTest($request->isTest);
        }

        $previousStatus = $listing->getStatus();
        $becamePublished = false;
        $statusChanged = false;
        if ($request->status !== null) {
            $statusChanged = $request->status !== $previousStatus;
            $listing->setStatus($request->status);
            if ($request->status === \App\Enum\ListingStatus::Published && $previousStatus !== \App\Enum\ListingStatus::Published) {
                $listing->setPublishedAt(new \DateTimeImmutable());
                $becamePublished = true;
            }
        }

        $city = $listing->getCity();
        $isTest = $listing->isTest();
        if ($request->city !== null) {
            $city = $this->locationTextResolver->resolveCity($request->city, $isTest);
            $listing->setCity($city);
        }
        if ($request->district !== null) {
            $city ??= $listing->getCity();
            $districtName = trim($request->district);
            if ($districtName === '') {
                $listing->setDistrict(null);
            } elseif ($city !== null) {
                $listing->setDistrict($this->locationTextResolver->resolveDistrict($city, $districtName, $isTest));
            }
        }
        if ($request->clearMetro) {
            $listing->setMetroStation(null);
        } elseif ($request->metro !== null) {
            $city ??= $listing->getCity();
            if ($city !== null) {
                $listing->setMetroStation($this->locationTextResolver->resolveMetroStation($city, $request->metro, $isTest));
            }
        }

        $listing->setPricePerSqm($this->calculatePricePerSqm($listing->getPrice(), $listing->getArea()));
        $this->listingGoodPriceEvaluator->apply($listing);

        if ($statusChanged && $request->status !== null) {
            $this->userNotificationService->notifyListingStatusChanged($listing, $previousStatus, $request->status);
        }

        $this->entityManager->flush();

        if ($becamePublished) {
            $this->messageBus->dispatch(new ListingCreatedMessage($listing->getId() ?? 0));
        }

        return ListingResponse::fromEntity($listing);
    }

    public function delete(int $id): void
    {
        $listing = $this->findEntity($id);
        $listing->softDelete();
        $this->entityManager->flush();
    }

    public function findEntity(int $id): Listing
    {
        $listing = $this->listingRepository->find($id);
        if (!$listing instanceof Listing) {
            throw new ResourceNotFoundException(ApiErrorCode::NOT_FOUND_LISTING);
        }

        return $listing;
    }

    private function calculatePricePerSqm(int $price, float $area): int
    {
        if ($area <= 0) {
            return 0;
        }

        return (int) round($price / $area);
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
}
