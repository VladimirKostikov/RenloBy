<?php

declare(strict_types=1);

namespace App\Dto\AiChat;

readonly class AiChatResponse
{
    public function __construct(
        public string $reply,
    ) {
    }
}
