<?php

declare(strict_types=1);

namespace App\Dto\Tariff;

use App\Entity\Tariff;

readonly class TariffResponse
{
    public function __construct(
        public int $id,
        public string $code,
        public string $priceUsd,
        public string $priceByn,
        public string $priceRub,
        public string $currency,
        public bool $isPopular,
        public int $sortOrder,
        public bool $isTest,
    ) {
    }

    public static function fromEntity(Tariff $tariff): self
    {
        return new self(
            $tariff->getId() ?? 0,
            $tariff->getCode(),
            $tariff->getPriceUsd(),
            $tariff->getPriceByn(),
            $tariff->getPriceRub(),
            $tariff->getCurrency(),
            $tariff->isPopular(),
            $tariff->getSortOrder(),
            $tariff->isTest(),
        );
    }
}
