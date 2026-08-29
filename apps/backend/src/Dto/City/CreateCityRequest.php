<?php

declare(strict_types=1);

namespace App\Dto\City;

readonly class CreateCityRequest
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $regionSlug = 'minsk-region',
        public ?bool $isTest = null,
    ) {
    }
}
