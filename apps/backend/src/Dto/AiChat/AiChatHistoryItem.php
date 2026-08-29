<?php

declare(strict_types=1);

namespace App\Dto\AiChat;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class AiChatHistoryItem
{
    public function __construct(
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Choice(choices: ['user', 'assistant'], message: ApiErrorCode::VALIDATION_FAILED)]
        public string $role,
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(max: 4000, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public string $content,
    ) {
    }
}
