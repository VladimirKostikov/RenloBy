<?php

declare(strict_types=1);

namespace App\Dto\SavedSearch;

use App\Entity\SavedSearch;

readonly class SavedSearchResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public array $filters,
        public int $userId,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(SavedSearch $savedSearch): self
    {
        return new self(
            $savedSearch->getId() ?? 0,
            $savedSearch->getName(),
            $savedSearch->getFilters(),
            $savedSearch->getUser()?->getId() ?? 0,
            $savedSearch->isTest(),
        );
    }
}
