<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Tariff;

class TariffFactory
{
    public function create(
        string $code,
        string $priceUsd,
        string $priceByn,
        string $priceRub,
        bool $isPopular = false,
        int $sortOrder = 0,
        string $currency = 'USD',
        bool $isTest = true,
    ): Tariff {
        return (new Tariff())
            ->setCode($code)
            ->setPriceUsd($priceUsd)
            ->setPriceByn($priceByn)
            ->setPriceRub($priceRub)
            ->setCurrency($currency)
            ->setIsPopular($isPopular)
            ->setSortOrder($sortOrder)
            ->setIsTest($isTest);
    }
}
