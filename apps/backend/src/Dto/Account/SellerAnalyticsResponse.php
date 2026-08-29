<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class SellerAnalyticsTopListing
{
    public function __construct(
        public int $id,
        public string $address,
        public int $views,
        public int $price,
        public string $dealType,
        public string $status,
    ) {
    }
}

readonly class SellerAnalyticsResponse
{
    /**
     * @param array<string, int> $byDealType
     * @param array<string, int> $byStatus
     * @param list<SellerAnalyticsTopListing> $topListings
     */
    public function __construct(
        public int $listingsCount,
        public int $publishedCount,
        public int $draftCount,
        public int $archivedCount,
        public int $totalViews,
        public float $avgViews,
        public array $byDealType,
        public array $byStatus,
        public array $topListings,
    ) {
    }
}
