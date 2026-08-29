<?php

declare(strict_types=1);

namespace App\Dto\Notification;

readonly class UnreadCountResponse
{
    public function __construct(
        public int $count,
    ) {
    }
}
