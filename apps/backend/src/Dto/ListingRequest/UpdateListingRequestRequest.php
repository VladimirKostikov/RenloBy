<?php

declare(strict_types=1);

namespace App\Dto\ListingRequest;

use App\Enum\ListingRequestStatus;

readonly class UpdateListingRequestRequest
{
    public function __construct(
        public ?ListingRequestStatus $status = null,
    ) {
    }
}
