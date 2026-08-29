<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsEngagement
{
    /**
     * @param list<ListingAnalyticsEngagementPoint> $series
     */
    public function __construct(
        public int $contactsTotal,
        public int $messagesTotal,
        public float $contactsAvg,
        public int $contactsPeak,
        public array $series,
    ) {
    }
}
