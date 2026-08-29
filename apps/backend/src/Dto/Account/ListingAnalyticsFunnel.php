<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsFunnel
{
    public function __construct(
        public int $views,
        public int $contacts,
        public int $messages,
        public float $viewToContactPct,
        public float $contactToMessagePct,
    ) {
    }
}
