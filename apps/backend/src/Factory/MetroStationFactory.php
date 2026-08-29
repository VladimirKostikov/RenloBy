<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\City;
use App\Entity\MetroStation;

class MetroStationFactory
{
    public function create(
        City $city,
        string $name = 'Metro',
        string $slug = 'metro',
        string $lineColor = '#0072BC',
        bool $isTest = false,
    ): MetroStation {
        return (new MetroStation())
            ->setName($name)
            ->setSlug($slug)
            ->setLineColor($lineColor)
            ->setCity($city)
            ->setIsTest($isTest);
    }
}
