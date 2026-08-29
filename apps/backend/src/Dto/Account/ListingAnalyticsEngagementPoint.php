<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsEngagementPoint
{
    public function __construct(
        public string $date,
        public int $contacts,
        public int $messages,
    ) {
    }
}
