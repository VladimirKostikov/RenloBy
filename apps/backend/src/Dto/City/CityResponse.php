<?php

declare(strict_types=1);

namespace App\Dto\City;

use App\Entity\City;

readonly class CityResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $regionSlug,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(City $city): self
    {
        return new self(
            $city->getId() ?? 0,
            $city->getName(),
            $city->getSlug(),
            $city->getRegionSlug(),
            $city->isTest(),
        );
    }
}
