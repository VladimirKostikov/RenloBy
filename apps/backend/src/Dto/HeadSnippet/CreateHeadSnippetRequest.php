<?php

declare(strict_types=1);

namespace App\Dto\HeadSnippet;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateHeadSnippetRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(max: 255)]
        public string $name,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(max: 100000)]
        public string $code,
        public bool $isEnabled = true,
        public int $sortOrder = 0,
        public ?bool $isTest = null,
    ) {
    }
}
