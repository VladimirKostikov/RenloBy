<?php

declare(strict_types=1);

namespace App\Message;

readonly class ListingCreatedMessage
{
    public function __construct(
        public int $listingId,
    ) {
    }
}
