<?php

declare(strict_types=1);

namespace App\Dto\Comparison;

use App\Dto\Listing\ListingResponse;
use App\Entity\Comparison;

readonly class ComparisonItemResponse
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public int $listingId,
        public ListingResponse $listing,
    ) {
    }

    public static function fromEntity(Comparison $comparison, ListingResponse $listing): self
    {
        return new self(
            $comparison->getId() ?? 0,
            $comparison->getUser()?->getId(),
            $comparison->getListing()?->getId() ?? 0,
            $listing,
        );
    }
}
