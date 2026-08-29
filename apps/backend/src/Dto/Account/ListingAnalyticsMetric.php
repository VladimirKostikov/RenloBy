<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsMetric
{
    public function __construct(
        public int $day,
        public int $week,
        public int $month,
        public float $dayChangePct,
        public float $weekChangePct,
        public float $monthChangePct,
    ) {
    }
}
