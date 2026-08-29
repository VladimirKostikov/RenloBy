<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsPromotionRow
{
    public function __construct(
        public string $metric,
        public int $before,
        public int $after,
        public float $growthPct,
    ) {
    }
}
