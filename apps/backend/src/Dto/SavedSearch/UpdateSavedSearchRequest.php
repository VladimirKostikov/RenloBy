<?php

declare(strict_types=1);

namespace App\Dto\SavedSearch;

readonly class UpdateSavedSearchRequest
{
    public function __construct(
        public ?string $name = null,
        public ?array $filters = null,
    ) {
    }
}
