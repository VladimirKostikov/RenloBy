<?php

declare(strict_types=1);

namespace App\Dto\Account;

readonly class ListingAnalyticsOption
{
    public function __construct(
        public int $id,
        public string $title,
        public string $address,
        public ?string $image,
        public int $rooms,
        public float $area,
        public string $status,
        public int $views,
    ) {
    }
}
