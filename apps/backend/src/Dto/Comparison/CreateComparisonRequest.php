<?php

declare(strict_types=1);

namespace App\Dto\Comparison;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateComparisonRequest
{
    public function __construct(
        #[Assert\Positive(message: ApiErrorCode::VALIDATION_LISTING_ID_INVALID)]
        public int $listingId,
    ) {
    }
}
