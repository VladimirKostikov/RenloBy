<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Repository\ListingRepository;
use App\Service\ListingMarketService;
use PHPUnit\Framework\TestCase;

final class ListingMarketServiceTest extends TestCase
{
    public function testSnapshotComparesSaleByPricePerSqm(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk');
        $district = (new District())->setName('Центральный')->setSlug('centralny')->setCity($city);
        $owner = new User();

        $listing = $this->makeListing($owner, $city, $district, DealType::Sale, 100000, 2000);
        $this->setListingId($listing, 10);

        $peerA = $this->makeListing($owner, $city, $district, DealType::Sale, 90000, 1800);
        $this->setListingId($peerA, 11);
        $peerB = $this->makeListing($owner, $city, $district, DealType::Sale, 110000, 2200);
        $this->setListingId($peerB, 12);

        $repository = $this->createStub(ListingRepository::class);
        $repository->method('findSimilarNearby')->willReturn([$peerA, $peerB]);

        $service = new ListingMarketService($repository);
        $snapshot = $service->snapshot($listing);

        self::assertSame('price_per_sqm', $snapshot->metric);
        self::assertSame(2000, $snapshot->current);
        self::assertSame(1800, $snapshot->min);
        self::assertSame(2200, $snapshot->max);
        self::assertSame(2000, $snapshot->avg);
        self::assertSame(2, $snapshot->similarCount);
        self::assertSame(0.0, $snapshot->changePct);
    }

    public function testSnapshotComparesRentByMonthlyPrice(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk');
        $district = (new District())->setName('Центральный')->setSlug('centralny')->setCity($city);
        $owner = new User();

        $listing = $this->makeListing($owner, $city, $district, DealType::Rent, 500, 10);
        $this->setListingId($listing, 20);
        $listing->setAiGoodPrice(true);

        $peer = $this->makeListing($owner, $city, $district, DealType::Rent, 600, 12);
        $this->setListingId($peer, 21);

        $repository = $this->createStub(ListingRepository::class);
        $repository->method('findSimilarNearby')->willReturn([$peer]);

        $service = new ListingMarketService($repository);
        $snapshot = $service->snapshot($listing);

        self::assertSame('price', $snapshot->metric);
        self::assertSame(500, $snapshot->current);
        self::assertTrue($snapshot->aiGoodPrice);
        self::assertLessThan(0, $snapshot->changePct);
    }

    private function makeListing(
        User $owner,
        City $city,
        District $district,
        DealType $dealType,
        int $price,
        int $pricePerSqm,
    ): Listing {
        return (new Listing())
            ->setUser($owner)
            ->setCity($city)
            ->setDistrict($district)
            ->setDealType($dealType)
            ->setListingType(ListingType::Apartment)
            ->setStatus(ListingStatus::Published)
            ->setPrice($price)
            ->setPricePerSqm($pricePerSqm)
            ->setRooms(2)
            ->setArea(50.0)
            ->setFloor(3)
            ->setTotalFloors(9)
            ->setAddress('Test')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setIsTest(true);
    }

    private function setListingId(Listing $listing, int $id): void
    {
        $reflection = new \ReflectionProperty(Listing::class, 'id');
        $reflection->setValue($listing, $id);
    }
}
