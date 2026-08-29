<?php

declare(strict_types=1);

namespace App\Dto\Telegram;

readonly class SellerTelegramStatusResponse
{
    public function __construct(
        public bool $configured,
        public bool $connected,
        public string $botUsername,
        public string $connectUrl,
        public ?string $username = null,
        public ?string $connectedAt = null,
    ) {
    }
}
