<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\City\CityResponse;
use App\Entity\City;
use PHPUnit\Framework\TestCase;

final class CityResponseTest extends TestCase
{
    public function testFromEntityIncludesIsTest(): void
    {
        $city = (new City())
            ->setName('Минск')
            ->setSlug('minsk')
            ->setRegionSlug('minsk-city')
            ->setIsTest(true);

        $response = CityResponse::fromEntity($city);

        self::assertTrue($response->isTest);
        self::assertSame('minsk', $response->slug);
    }
}
