<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateProfileRequest
{
    public function __construct(
        #[Assert\Length(max: 80, maxMessage: ApiErrorCode::VALIDATION_NAME_LENGTH)]
        public ?string $lastName = null,
        #[Assert\Length(max: 80, maxMessage: ApiErrorCode::VALIDATION_NAME_LENGTH)]
        public ?string $firstName = null,
        #[Assert\Length(max: 80, maxMessage: ApiErrorCode::VALIDATION_NAME_LENGTH)]
        public ?string $patronymic = null,
        #[Assert\Length(max: 32, maxMessage: ApiErrorCode::VALIDATION_PHONE_INVALID)]
        public ?string $phone = null,
        #[Assert\Length(max: 500, maxMessage: ApiErrorCode::VALIDATION_PHOTO_INVALID)]
        public ?string $photo = null,
        #[Assert\Length(max: 120, maxMessage: ApiErrorCode::VALIDATION_SOCIAL_INVALID)]
        public ?string $instagram = null,
        #[Assert\Length(max: 120, maxMessage: ApiErrorCode::VALIDATION_SOCIAL_INVALID)]
        public ?string $telegram = null,
        #[Assert\Length(max: 120, maxMessage: ApiErrorCode::VALIDATION_SOCIAL_INVALID)]
        public ?string $whatsapp = null,
        #[Assert\Length(max: 120, maxMessage: ApiErrorCode::VALIDATION_SOCIAL_INVALID)]
        public ?string $viber = null,
    ) {
    }
}
