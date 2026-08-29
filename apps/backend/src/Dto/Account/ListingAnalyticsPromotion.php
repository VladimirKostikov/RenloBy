<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsPromotion
{
    /**
     * @param list<ListingAnalyticsPromotionRow> $rows
     */
    public function __construct(
        public bool $active,
        public ?string $tariff,
        public array $rows,
    ) {
    }
}
