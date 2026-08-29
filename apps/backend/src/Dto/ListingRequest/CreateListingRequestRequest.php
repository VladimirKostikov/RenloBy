<?php

declare(strict_types=1);

namespace App\Dto\ListingRequest;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateListingRequestRequest
{
    public const MESSAGE_MIN_LENGTH = 10;
    public const MESSAGE_MAX_LENGTH = 2000;
    public const NAME_MAX_LENGTH = 120;
    public const PHONE_MAX_LENGTH = 32;

    public function __construct(
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(
            max: self::PHONE_MAX_LENGTH,
            maxMessage: ApiErrorCode::VALIDATION_FAILED,
        )]
        #[Assert\Regex(
            pattern: '/^\+?[0-9\s\-()]{7,32}$/',
            message: ApiErrorCode::VALIDATION_FAILED,
        )]
        public string $phone,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(
            min: self::MESSAGE_MIN_LENGTH,
            max: self::MESSAGE_MAX_LENGTH,
            minMessage: ApiErrorCode::VALIDATION_FAILED,
            maxMessage: ApiErrorCode::VALIDATION_FAILED,
        )]
        public string $message,
        #[Assert\Length(
            max: self::NAME_MAX_LENGTH,
            maxMessage: ApiErrorCode::VALIDATION_FAILED,
        )]
        public ?string $name = null,
    ) {
    }
}
