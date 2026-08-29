<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsDetail
{
    /**
     * @param list<ListingAnalyticsSeriesPoint> $viewsSeries
     */
    public function __construct(
        public ListingAnalyticsOption $listing,
        public string $updatedAt,
        public ListingAnalyticsMetric $views,
        public int $contactOpensWeek,
        public float $contactOpensChangePct,
        public int $messagesWeek,
        public float $messagesChangePct,
        public float $conversionPct,
        public float $conversionChangePct,
        public array $viewsSeries,
        public ListingAnalyticsFunnel $funnel,
        public ListingAnalyticsPromotion $promotion,
        public ListingAnalyticsEngagement $engagement,
    ) {
    }
}
