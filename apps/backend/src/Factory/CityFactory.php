<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\City;

class CityFactory
{
    public function create(string $name = 'City', string $slug = 'city', string $regionSlug = 'minsk-region', bool $isTest = false): City
    {
        return (new City())
            ->setName($name)
            ->setSlug($slug)
            ->setRegionSlug($regionSlug)
            ->setIsTest($isTest);
    }
}
