<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsSeriesPoint
{
    public function __construct(
        public string $date,
        public int $views,
        public float $average,
    ) {
    }
}
