<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\Listing\ListingResponse;
use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use PHPUnit\Framework\TestCase;

final class ListingResponseNullableFloorTest extends TestCase
{
    public function testMapsNullFloorAndMetro(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-region');
        $district = (new District())->setName('Центр')->setSlug('center')->setCity($city);
        $user = (new User())->setEmail('floor-null@renlo.local');

        $listing = (new Listing())
            ->setUser($user)
            ->setCity($city)
            ->setDistrict($district)
            ->setDealType(DealType::Sale)
            ->setListingType(ListingType::Apartment)
            ->setStatus(ListingStatus::Published)
            ->setPrice(100000)
            ->setPricePerSqm(2000)
            ->setRooms(2)
            ->setArea(50.0)
            ->setFloor(null)
            ->setTotalFloors(null)
            ->setAddress('ул. Ленина, 1')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setMetroMinutes(null)
            ->setIsTest(true);

        $response = ListingResponse::fromEntity($listing);

        self::assertNull($response->floor);
        self::assertNull($response->totalFloors);
        self::assertNull($response->metroMinutes);
        self::assertNull($response->metroStationId);
    }

    public function testMapsNullDistrict(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-region');
        $user = (new User())->setEmail('district-null@renlo.local');

        $listing = (new Listing())
            ->setUser($user)
            ->setCity($city)
            ->setDistrict(null)
            ->setDealType(DealType::Sale)
            ->setListingType(ListingType::Apartment)
            ->setStatus(ListingStatus::Published)
            ->setPrice(100000)
            ->setPricePerSqm(2000)
            ->setRooms(2)
            ->setArea(50.0)
            ->setAddress('ул. Ленина, 1')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setIsTest(true);

        $response = ListingResponse::fromEntity($listing);

        self::assertNull($response->districtId);
        self::assertSame('', $response->districtName);
    }
}
