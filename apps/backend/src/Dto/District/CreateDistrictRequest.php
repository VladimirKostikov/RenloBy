<?php

declare(strict_types=1);

namespace App\Dto\District;

readonly class CreateDistrictRequest
{
    public function __construct(
        public string $name,
        public string $slug,
        public int $cityId,
        public ?bool $isTest = null,
    ) {
    }
}
