<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Account\ListingAnalyticsDetail;
use App\Entity\City;
use App\Entity\District;
use App\Entity\Listing;
use App\Entity\User;
use App\Enum\DealType;
use App\Enum\ListingStatus;
use App\Enum\ListingType;
use App\Repository\ListingDailyStatRepository;
use App\Repository\ListingRepository;
use App\Repository\PaymentTransactionRepository;
use App\Service\ListingAnalyticsService;
use PHPUnit\Framework\TestCase;

final class ListingAnalyticsServiceTest extends TestCase
{
    public function testGetDetailBuildsPerListingPayload(): void
    {
        $user = (new User())->setEmail('owner@test.local')->setName('Owner');
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setAccessible(true);
        $ref->setValue($user, 7);

        $city = (new City())->setName('Минск')->setSlug('minsk')->setRegionSlug('minsk-city');
        $district = (new District())->setCity($city)->setName('Центр')->setSlug('center');
        $listing = (new Listing())
            ->setDealType(DealType::Sale)
            ->setListingType(ListingType::Apartment)
            ->setStatus(ListingStatus::Published)
            ->setPrice(120000)
            ->setPricePerSqm(2000)
            ->setRooms(2)
            ->setArea(60)
            ->setFloor(3)
            ->setTotalFloors(9)
            ->setAddress('ул. Тест, 10')
            ->setLatitude(53.9)
            ->setLongitude(27.5)
            ->setViews(100)
            ->setContactOpens(8)
            ->setMessages(3)
            ->setImages(['https://example.com/a.jpg'])
            ->setPublishedAt(new \DateTimeImmutable('-2 days'))
            ->setUser($user)
            ->setCity($city)
            ->setDistrict($district);
        $listingRef = new \ReflectionProperty(Listing::class, 'id');
        $listingRef->setAccessible(true);
        $listingRef->setValue($listing, 42);

        $listingRepository = $this->createMock(ListingRepository::class);
        $listingRepository->method('find')->with(42)->willReturn($listing);
        $listingRepository->method('findSimilarNearby')->willReturn([]);
        $listingRepository->method('findByUserForAnalytics')->willReturn([
            'items' => [$listing],
            'total' => 1,
        ]);

        $dailyStatRepository = $this->createMock(ListingDailyStatRepository::class);
        $dailyStatRepository->method('findForListingSince')->willReturn([]);

        $paymentRepository = $this->createMock(PaymentTransactionRepository::class);
        $paymentRepository->method('findBy')->willReturn([]);

        $service = new ListingAnalyticsService(
            $listingRepository,
            $dailyStatRepository,
            $paymentRepository,
        );

        $detail = $service->getDetail($user, 42, 'week');

        self::assertInstanceOf(ListingAnalyticsDetail::class, $detail);
        self::assertSame(42, $detail->listing->id);
        self::assertNotEmpty($detail->viewsSeries);
        self::assertSame($detail->views->week, $detail->funnel->views);
        self::assertSame($detail->contactOpensWeek, $detail->funnel->contacts);
        self::assertSame($detail->messagesWeek, $detail->funnel->messages);
        self::assertCount(7, $detail->engagement->series);

        $options = $service->listOptions($user, 1, 20, '');
        self::assertSame(1, $options->total);
        self::assertCount(1, $options->items);
        self::assertSame(42, $options->items[0]->id);
    }
}
