<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class AccountSummaryResponse
{
    public function __construct(
        public int $listingsCount,
        public int $favoritesCount,
        public int $comparisonsCount,
        public int $savedSearchesCount,
    ) {
    }
}
