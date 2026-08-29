<?php

declare(strict_types=1);

namespace App\Dto\District;

use App\Entity\District;

readonly class DistrictResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public int $cityId,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(District $district): self
    {
        return new self(
            $district->getId() ?? 0,
            $district->getName(),
            $district->getSlug(),
            $district->getCity()?->getId() ?? 0,
            $district->isTest(),
        );
    }
}
