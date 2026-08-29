<?php

declare(strict_types=1);

namespace App\Dto\Common;

readonly class PaginatedResponse
{
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }
}
