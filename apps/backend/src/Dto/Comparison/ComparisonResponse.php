<?php

declare(strict_types=1);

namespace App\Dto\Comparison;

use App\Entity\Comparison;

readonly class ComparisonResponse
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public int $listingId,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(Comparison $comparison): self
    {
        return new self(
            $comparison->getId() ?? 0,
            $comparison->getUser()?->getId(),
            $comparison->getListing()?->getId() ?? 0,
            $comparison->isTest(),
        );
    }
}
