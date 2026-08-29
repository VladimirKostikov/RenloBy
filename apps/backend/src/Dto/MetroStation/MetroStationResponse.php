<?php

declare(strict_types=1);

namespace App\Dto\MetroStation;

use App\Entity\MetroStation;

readonly class MetroStationResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public string $lineColor,
        public int $cityId,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(MetroStation $station): self
    {
        return new self(
            $station->getId() ?? 0,
            $station->getName(),
            $station->getSlug(),
            $station->getLineColor(),
            $station->getCity()?->getId() ?? 0,
            $station->isTest(),
        );
    }
}
