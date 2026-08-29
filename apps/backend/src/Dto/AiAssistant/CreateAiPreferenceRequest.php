<?php

declare(strict_types=1);

namespace App\Dto\AiAssistant;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateAiPreferenceRequest
{
    /**
     * @param array<string, mixed> $answers
     */
    public function __construct(
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Type(type: 'array', message: ApiErrorCode::VALIDATION_FAILED)]
        public array $answers,
    ) {
    }
}
