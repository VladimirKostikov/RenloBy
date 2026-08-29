<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Repository\ListingRepository;
use App\Service\CurrencyConverter;
use App\Service\ListingGoodPriceEvaluator;
use PHPUnit\Framework\TestCase;

final class ListingGoodPriceEvaluatorTest extends TestCase
{
    public function testMarksBelowMarketSaleAsGoodPrice(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $district = (new District())->setName('Центральный')->setSlug('centralny')->setCity($city);

        $target = $this->listing($city, $district, DealType::Sale, price: 80000, area: 50.0, rooms: 2);
        $peers = [];
        for ($i = 0; $i < 8; ++$i) {
            $peers[] = $this->listing($city, $district, DealType::Sale, price: 100000 + ($i * 1000), area: 50.0, rooms: 2);
        }

        $repository = $this->createStub(ListingRepository::class);
        $repository->method('findMarketComps')->willReturn($peers);

        $evaluator = new ListingGoodPriceEvaluator($repository, new CurrencyConverter(3.27, 93));
        $verdict = $evaluator->evaluate($target);

        self::assertTrue($verdict->isGoodPrice);
        self::assertGreaterThanOrEqual(70, $verdict->score);
        self::assertLessThan(0, $verdict->deltaPct);
        self::assertSame('price_per_sqm_usd', $verdict->metric);
    }

    public function testDoesNotMarkAboveMarketRent(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $district = (new District())->setName('Центральный')->setSlug('centralny')->setCity($city);

        $target = $this->listing($city, $district, DealType::Rent, price: 900, area: 45.0, rooms: 2);
        $peers = [];
        for ($i = 0; $i < 8; ++$i) {
            $peers[] = $this->listing($city, $district, DealType::Rent, price: 500 + ($i * 10), area: 45.0, rooms: 2);
        }

        $repository = $this->createStub(ListingRepository::class);
        $repository->method('findMarketComps')->willReturn($peers);

        $evaluator = new ListingGoodPriceEvaluator($repository, new CurrencyConverter(3.27, 93));
        $verdict = $evaluator->evaluate($target);

        self::assertFalse($verdict->isGoodPrice);
        self::assertSame('monthly_price_usd', $verdict->metric);
    }

    public function testInsufficientPeersReturnsFalse(): void
    {
        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $district = (new District())->setName('Центральный')->setSlug('centralny')->setCity($city);
        $target = $this->listing($city, $district, DealType::Sale, price: 70000, area: 48.0, rooms: 2);

        $repository = $this->createStub(ListingRepository::class);
        $repository->method('findMarketComps')->willReturn([
            $this->listing($city, $district, DealType::Sale, price: 90000, area: 48.0, rooms: 2),
        ]);

        $evaluator = new ListingGoodPriceEvaluator($repository, new CurrencyConverter(3.27, 93));
        $verdict = $evaluator->evaluate($target);

        self::assertFalse($verdict->isGoodPrice);
        self::assertContains('insufficient_peers', $verdict->signals);
    }

    private function listing(
        City $city,
        District $district,
        DealType $dealType,
        int $price,
        float $area,
        int $rooms,
    ): Listing {
        return (new Listing())
            ->setDealType($dealType)
            ->setListingType(ListingType::Apartment)
            ->setStatus(ListingStatus::Published)
            ->setPrice($price)
            ->setPricePerSqm($area > 0 ? (int) round($price / $area) : 0)
            ->setRooms($rooms)
            ->setArea($area)
            ->setFloor(5)
            ->setTotalFloors(9)
            ->setAddress('ул. Тестовая, 1')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setFromOwner(true)
            ->setNoCommission(true)
            ->setCity($city)
            ->setDistrict($district)
            ->setIsTest(false);
    }
}
