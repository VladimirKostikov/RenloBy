<?php

declare(strict_types=1);

namespace App\Dto\Favorite;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateFavoriteRequest
{
    public function __construct(
        #[Assert\Positive(message: ApiErrorCode::VALIDATION_LISTING_ID_INVALID)]
        public int $listingId,
    ) {
    }
}
