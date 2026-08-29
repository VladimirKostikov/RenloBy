<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Account\SellerAnalyticsResponse;
use App\Dto\Account\SellerAnalyticsTopListing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Repository\ListingRepository;

class SellerAnalyticsService
{
    public function __construct(
        private readonly ListingRepository $listingRepository,
    ) {
    }

    public function getAnalytics(User $user): SellerAnalyticsResponse
    {
        $listingsCount = $this->listingRepository->countByUser($user);
        $publishedCount = $this->listingRepository->countByUserAndStatus($user, ListingStatus::Published);
        $draftCount = $this->listingRepository->countByUserAndStatus($user, ListingStatus::Draft);
        $archivedCount = $this->listingRepository->countByUserAndStatus($user, ListingStatus::Archived);
        $totalViews = $this->listingRepository->sumViewsByUser($user);
        $avgViews = $listingsCount > 0 ? round($totalViews / $listingsCount, 1) : 0.0;

        $byDealType = [
            DealType::Sale->value => 0,
            DealType::Rent->value => 0,
        ];
        foreach ($this->listingRepository->countGroupedByDealType($user) as $row) {
            $key = $row['dealType'] instanceof DealType
                ? $row['dealType']->value
                : (string) $row['dealType'];
            if (array_key_exists($key, $byDealType)) {
                $byDealType[$key] = (int) $row['count'];
            }
        }

        $byStatus = [
            ListingStatus::Draft->value => 0,
            ListingStatus::Pending->value => 0,
            ListingStatus::Published->value => 0,
            ListingStatus::Rejected->value => 0,
            ListingStatus::Archived->value => 0,
        ];
        foreach ($this->listingRepository->countGroupedByStatus($user) as $row) {
            $key = $row['status'] instanceof ListingStatus
                ? $row['status']->value
                : (string) $row['status'];
            $byStatus[$key] = (int) $row['count'];
        }

        $topListings = array_map(
            static fn ($listing) => new SellerAnalyticsTopListing(
                $listing->getId() ?? 0,
                $listing->getAddress(),
                $listing->getViews(),
                $listing->getPrice(),
                $listing->getDealType()->value,
                $listing->getStatus()->value,
            ),
            $this->listingRepository->findByUserOrderedByViews($user, 5),
        );

        return new SellerAnalyticsResponse(
            $listingsCount,
            $publishedCount,
            $draftCount,
            $archivedCount,
            $totalViews,
            $avgViews,
            $byDealType,
            $byStatus,
            $topListings,
        );
    }
}
