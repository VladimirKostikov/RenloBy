<?php

declare(strict_types=1);

namespace App\Dto\District;

readonly class UpdateDistrictRequest
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?int $cityId = null,
        public ?bool $isTest = null,
    ) {
    }
}
