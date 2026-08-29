<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Listing\AddressSuggestItemResponse;
use App\Repository\ListingRepository;
use App\Service\ListingAddressNormalizer;
use App\Service\ListingAddressSuggestService;
use PHPUnit\Framework\TestCase;

final class ListingAddressSuggestServiceTest extends TestCase
{
    public function testSuggestsStreetDistrictMetroAndCityFromListings(): void
    {
        $repository = $this->createMock(ListingRepository::class);
        $repository->expects(self::once())
            ->method('findAddressSuggestCandidates')
            ->with('незав', 80)
            ->willReturn([
                [
                    'address' => 'пр. Независимости, 25',
                    'cityId' => 1,
                    'cityName' => 'Минск',
                    'districtId' => 10,
                    'districtName' => 'Центральный',
                    'metroStationId' => 5,
                    'metroName' => 'Независимости',
                ],
                [
                    'address' => 'ул. Другая, 1',
                    'cityId' => 1,
                    'cityName' => 'Минск',
                    'districtId' => 10,
                    'districtName' => 'Центральный',
                    'metroStationId' => null,
                    'metroName' => null,
                ],
            ]);

        $service = new ListingAddressSuggestService($repository, new ListingAddressNormalizer());
        $items = $service->suggest('незав', 10);

        $kinds = array_map(static fn (AddressSuggestItemResponse $item): string => $item->kind, $items);
        self::assertContains('street', $kinds);
        self::assertContains('metro', $kinds);

        $street = null;
        foreach ($items as $item) {
            if ($item->kind === 'street') {
                $street = $item;
                break;
            }
        }

        self::assertNotNull($street);
        self::assertSame('пр. Независимости', $street->label);
        self::assertSame('Минск', $street->subtitle);
        self::assertSame(1, $street->cityId);
    }

    public function testReturnsEmptyForShortQuery(): void
    {
        $repository = $this->createMock(ListingRepository::class);
        $repository->expects(self::never())->method('findAddressSuggestCandidates');

        $service = new ListingAddressSuggestService($repository, new ListingAddressNormalizer());
        self::assertSame([], $service->suggest('м'));
    }

    public function testDoesNotSuggestRegions(): void
    {
        $repository = $this->createStub(ListingRepository::class);
        $repository->method('findAddressSuggestCandidates')->willReturn([
            [
                'address' => 'ул. Советская, 1',
                'cityId' => 2,
                'cityName' => 'Брест',
                'districtId' => 20,
                'districtName' => 'Ленинский',
                'metroStationId' => null,
                'metroName' => null,
            ],
        ]);

        $service = new ListingAddressSuggestService($repository, new ListingAddressNormalizer());
        $items = $service->suggest('брест', 10);

        $kinds = array_map(static fn (AddressSuggestItemResponse $item): string => $item->kind, $items);
        self::assertNotContains('region', $kinds);
        self::assertContains('city', $kinds);
    }
}
