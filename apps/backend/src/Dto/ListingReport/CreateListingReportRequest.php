<?php

declare(strict_types=1);

namespace App\Dto\ListingReport;

use App\Enum\ListingReportReason;
use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateListingReportRequest
{
    public const COMMENT_MIN_LENGTH = 30;
    public const COMMENT_MAX_LENGTH = 2000;

    public function __construct(
        #[Assert\NotNull(message: ApiErrorCode::VALIDATION_FAILED)]
        public ListingReportReason $reason,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(
            min: self::COMMENT_MIN_LENGTH,
            max: self::COMMENT_MAX_LENGTH,
            minMessage: ApiErrorCode::VALIDATION_FAILED,
            maxMessage: ApiErrorCode::VALIDATION_FAILED,
        )]
        public string $comment,
    ) {
    }
}
