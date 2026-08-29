<?php

declare(strict_types=1);

namespace App\Dto\City;

readonly class UpdateCityRequest
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $regionSlug = null,
        public ?bool $isTest = null,
    ) {
    }
}
