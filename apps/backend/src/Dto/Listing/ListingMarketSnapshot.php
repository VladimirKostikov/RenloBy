<?php

declare(strict_types=1);

namespace App\Dto\Listing;

readonly class ListingMarketSnapshot
{
    public function __construct(
        public string $metric,
        public int $current,
        public int $avg,
        public int $min,
        public int $max,
        public int $rank,
        public int $similarCount,
        public float $changePct,
        public bool $aiGoodPrice,
    ) {
    }
}
