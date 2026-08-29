<?php

declare(strict_types=1);

namespace App\Dto\SavedSearch;

readonly class CreateSavedSearchRequest
{
    public function __construct(
        public string $name,
        public array $filters,
    ) {
    }
}
