<?php

declare(strict_types=1);

namespace App\Dto\Favorite;

use App\Dto\Listing\ListingResponse;
use App\Entity\Favorite;

readonly class FavoriteItemResponse
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public int $listingId,
        public ListingResponse $listing,
    ) {
    }

    public static function fromEntity(Favorite $favorite, ListingResponse $listing): self
    {
        return new self(
            $favorite->getId() ?? 0,
            $favorite->getUser()?->getId(),
            $favorite->getListing()?->getId() ?? 0,
            $listing,
        );
    }
}
