<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\City;
use App\Entity\District;

class DistrictFactory
{
    public function create(City $city, string $name = 'District', string $slug = 'district', bool $isTest = false): District
    {
        return (new District())
            ->setName($name)
            ->setSlug($slug)
            ->setCity($city)
            ->setIsTest($isTest);
    }
}
