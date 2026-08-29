<?php

declare(strict_types=1);

namespace App\Dto\Listing;

readonly class ListingGoodPriceVerdict
{
    /**
     * @param list<string> $signals
     */
    public function __construct(
        public bool $isGoodPrice,
        public int $score,
        public float $deltaPct,
        public int $peerCount,
        public int $referenceValue,
        public int $listingValue,
        public string $metric,
        public array $signals,
    ) {
    }
}
