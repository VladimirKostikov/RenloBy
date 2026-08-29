<?php

declare(strict_types=1);

namespace App\Dto\AiChat;

use App\Http\ApiErrorCode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class AiChatRequest
{
    /**
     * @param list<AiChatHistoryItem> $history
     */
    public function __construct(
        #[Assert\NotBlank(message: ApiErrorCode::VALIDATION_FAILED)]
        #[Assert\Length(min: 1, max: 2000, maxMessage: ApiErrorCode::VALIDATION_FAILED)]
        public string $message,
        #[Assert\Count(max: 20)]
        #[Assert\Valid]
        public array $history = [],
        #[Assert\Choice(choices: ['ru', 'en'], message: ApiErrorCode::VALIDATION_FAILED)]
        public string $locale = 'ru',
    ) {
    }
}
