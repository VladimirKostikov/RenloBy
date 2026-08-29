<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_EMAIL_REQUIRED)]
        #[Assert\Email(message: ApiErrorCode::VALIDATION_EMAIL_INVALID)]
        public string $email,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_PASSWORD_REQUIRED)]
        #[Assert\Length(
            min: 8,
            max: 128,
            minMessage: ApiErrorCode::VALIDATION_PASSWORD_MIN,
            maxMessage: ApiErrorCode::VALIDATION_PASSWORD_MIN,
        )]
        public string $password,
    ) {
    }
}
