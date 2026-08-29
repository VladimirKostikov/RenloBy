<?php

declare(strict_types=1);

namespace App\Dto\Favorite;

use App\Entity\Favorite;

readonly class FavoriteResponse
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public int $listingId,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(Favorite $favorite): self
    {
        return new self(
            $favorite->getId() ?? 0,
            $favorite->getUser()?->getId(),
            $favorite->getListing()?->getId() ?? 0,
            $favorite->isTest(),
        );
    }
}
